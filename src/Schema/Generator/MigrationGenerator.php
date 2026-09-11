<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generator;

use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlScheme;

/**
 * Emits Laravel migrations for TDLib-style domain tables + JSONB (spec §4).
 *
 * File layout (deterministic):
 *  - one file per domain table (~13 files)
 *  - one file with the method route table
 *
 * Each domain table stores extracted query columns + tl_data JSONB + constructor_id.
 * The old 393-per-constructor tables, child tables, and FK migrations are removed.
 */
final class MigrationGenerator
{
    public const DATE_TOKEN = '2026_08_28';

    /** @var array<string, string> table => migration filename */
    private array $tableMap = [];
    private string $currentFile = '';
    private string $currentTable = '';

    /**
     * Domain table definitions: ordered list of domain => DDL builder.
     *
     * Each entry returns the PHP Blueprint lines for Schema::create().
     *
     * @var array<string, callable(): list<string>>
     */
    private const DOMAIN_TABLES = [
        'users'                 => [self::class, 'ddlUsers'],
        'chats'                 => [self::class, 'ddlChats'],
        'channels'              => [self::class, 'ddlChannels'],
        'messages'              => [self::class, 'ddlMessages'],
        'dialogs'               => [self::class, 'ddlDialogs'],
        'updates'               => [self::class, 'ddlUpdates'],
        'documents'             => [self::class, 'ddlDocuments'],
        'photos'                => [self::class, 'ddlPhotos'],
        'sticker_sets'          => [self::class, 'ddlStickerSets'],
        'stories'               => [self::class, 'ddlStories'],
        'wallpapers'            => [self::class, 'ddlWallpapers'],
        'channel_participants'  => [self::class, 'ddlChannelParticipants'],
    ];

    /** @return array<string,string> filename => content */
    public function generate(TlScheme $scheme): array
    {
        $this->tableMap = [];
        $files = [];

        // Emit one migration per domain table
        $seq = 0;
        foreach (self::DOMAIN_TABLES as $domain => $ddlBuilder) {
            $seq++;
            $table = Naming::domainTable($domain);
            $this->currentTable = $table;
            $this->currentFile = sprintf('%s_%06d_create_%s_table.php', self::DATE_TOKEN, $seq, $table);
            $this->tableMap[$table] = $this->currentFile;
            $files[$this->currentFile] = $this->domainMigration($domain, $table, $ddlBuilder);
        }

        // Route table migration
        $seq++;
        $this->currentFile = sprintf('%s_%06d_create_tf_routes_table.php', self::DATE_TOKEN, $seq);
        $this->tableMap['tf_routes'] = $this->currentFile;
        $files[$this->currentFile] = $this->routeMigration($scheme);

        return $files;
    }

    /** @return array{tables: array<string,string>, fk_count: int} */
    public function stats(): array
    {
        return ['tables' => $this->tableMap, 'fk_count' => 0];
    }

    /**
     * Index names the PG way: content-addressed to avoid collisions.
     */
    private function indexLine(string $col): string
    {
        return "    \$table->index('{$col}', 'ix_" . substr(sha1($this->currentTable . ':' . $col), 0, 24) . "');";
    }

    private function domainMigration(string $domain, string $table, callable $ddlBuilder): string
    {
        $up = [];
        $down = [];

        $up[] = "Schema::create('{$table}', function (Blueprint \$table) {";
        foreach ($ddlBuilder() as $line) {
            $up[] = $line;
        }
        $up[] = "});";
        $down[] = "Schema::dropIfExists('{$table}');";

        return CodeWriter::migrationFile($up, array_reverse($down));
    }

    // ── Domain DDL Builders ──────────────────────────────────────────

    /** @return list<string> */
    private static function ddlUsers(): array
    {
        return [
            "    \$table->bigInteger('id');",
            "    \$table->bigInteger('constructor_id');",
            "    \$table->bigInteger('account_id');",
            "    \$table->bigInteger('access_hash')->nullable();",
            "    \$table->text('first_name')->nullable();",
            "    \$table->text('last_name')->nullable();",
            "    \$table->text('username')->nullable();",
            "    \$table->text('phone')->nullable();",
            "    \$table->boolean('is_bot')->default(false);",
            "    \$table->boolean('is_self')->default(false);",
            "    \$table->boolean('is_contact')->default(false);",
            "    \$table->boolean('is_premium')->default(false);",
            "    \$table->boolean('is_deleted')->default(false);",
            "    \$table->bigInteger('photo_id')->nullable();",
            "    \$table->text('status_type')->nullable();",
            "    \$table->jsonb('tl_data');",
            "    \$table->timestamps();",
            "    \$table->primary(['id', 'account_id']);",
            "    \$table->index('username', 'ix_tf_users_username_partial')->where('username');",
            "    \$table->index('phone', 'ix_tf_users_phone_partial')->where('phone');",
            "    \$table->index('account_id', 'ix_tf_users_account_id');",
        ];
    }

    /** @return list<string> */
    private static function ddlChats(): array
    {
        return [
            "    \$table->bigInteger('id');",
            "    \$table->bigInteger('constructor_id');",
            "    \$table->bigInteger('account_id');",
            "    \$table->text('title')->nullable();",
            "    \$table->integer('participants_count')->nullable();",
            "    \$table->integer('version')->nullable();",
            "    \$table->integer('date')->nullable();",
            "    \$table->boolean('is_deactivated')->default(false);",
            "    \$table->boolean('is_left')->default(false);",
            "    \$table->jsonb('tl_data');",
            "    \$table->timestamps();",
            "    \$table->primary(['id', 'account_id']);",
            "    \$table->index('account_id', 'ix_tf_chats_account_id');",
        ];
    }

    /** @return list<string> */
    private static function ddlChannels(): array
    {
        return [
            "    \$table->bigInteger('id');",
            "    \$table->bigInteger('constructor_id');",
            "    \$table->bigInteger('account_id');",
            "    \$table->bigInteger('access_hash')->nullable();",
            "    \$table->text('title')->nullable();",
            "    \$table->text('username')->nullable();",
            "    \$table->integer('date')->nullable();",
            "    \$table->integer('participants_count')->nullable();",
            "    \$table->boolean('is_broadcast')->default(false);",
            "    \$table->boolean('is_megagroup')->default(false);",
            "    \$table->boolean('is_verified')->default(false);",
            "    \$table->boolean('is_restricted')->default(false);",
            "    \$table->boolean('is_left')->default(false);",
            "    \$table->boolean('is_forum')->default(false);",
            "    \$table->text('restriction_reason')->nullable();",
            "    \$table->jsonb('tl_data');",
            "    \$table->timestamps();",
            "    \$table->primary(['id', 'account_id']);",
            "    \$table->index('username', 'ix_tf_channels_username_partial')->where('username');",
            "    \$table->index('account_id', 'ix_tf_channels_account_id');",
        ];
    }

    /** @return list<string> */
    private static function ddlMessages(): array
    {
        return [
            "    \$table->bigInteger('id');",
            "    \$table->integer('message_id');",
            "    \$table->bigInteger('peer_id');",
            "    \$table->bigInteger('from_id')->nullable();",
            "    \$table->integer('date');",
            "    \$table->bigInteger('constructor_id');",
            "    \$table->bigInteger('account_id');",
            "    \$table->primary('id');",
            "    \$table->boolean('is_out')->default(false);",
            "    \$table->boolean('is_mentioned')->default(false);",
            "    \$table->boolean('is_silent')->default(false);",
            "    \$table->boolean('is_pinned')->default(false);",
            "    \$table->text('message_text')->nullable();",
            "    \$table->text('media_type')->nullable();",
            "    \$table->integer('reply_to_msg_id')->nullable();",
            "    \$table->jsonb('tl_data');",
            "    \$table->timestamps();",
            "    \$table->unique(['peer_id', 'message_id', 'account_id'], 'ux_tf_messages_scope');",
            "    \$table->index(['peer_id', 'date'], 'ix_tf_messages_peer_date');",
            "    \$table->index('from_id', 'ix_tf_messages_from_id')->where('from_id');",
            "    \$table->index('account_id', 'ix_tf_messages_account_id');",
        ];
    }

    /** @return list<string> */
    private static function ddlDialogs(): array
    {
        return [
            "    \$table->bigInteger('id');",
            "    \$table->primary('id');",
            "    \$table->bigInteger('peer_id');",
            "    \$table->text('peer_type');",
            "    \$table->bigInteger('account_id');",
            "    \$table->integer('top_message_id')->nullable();",
            "    \$table->integer('unread_count')->default(0);",
            "    \$table->integer('unread_mentions')->default(0);",
            "    \$table->boolean('is_pinned')->default(false);",
            "    \$table->integer('folder_id')->default(0);",
            "    \$table->integer('pts')->nullable();",
            "    \$table->jsonb('tl_data');",
            "    \$table->timestamps();",
            "    \$table->unique(['peer_id', 'account_id'], 'ux_tf_dialogs_scope');",
            "    \$table->index('account_id', 'ix_tf_dialogs_account_id');",
        ];
    }

    /** @return list<string> */
    private static function ddlUpdates(): array
    {
        return [
            "    \$table->bigInteger('id');",
            "    \$table->primary('id');",
            "    \$table->bigInteger('constructor_id');",
            "    \$table->bigInteger('account_id');",
            "    \$table->bigInteger('peer_id')->nullable();",
            "    \$table->integer('message_id')->nullable();",
            "    \$table->bigInteger('user_id')->nullable();",
            "    \$table->integer('pts')->nullable();",
            "    \$table->integer('pts_count')->nullable();",
            "    \$table->integer('date')->nullable();",
            "    \$table->jsonb('tl_data');",
            "    \$table->timestamps();",
            "    \$table->index('account_id', 'ix_tf_updates_account_id');",
            "    \$table->index(['peer_id', 'account_id'], 'ix_tf_updates_peer_account');",
        ];
    }

    /** @return list<string> */
    private static function ddlDocuments(): array
    {
        return [
            "    \$table->bigInteger('id');",
            "    \$table->bigInteger('constructor_id');",
            "    \$table->bigInteger('account_id');",
            "    \$table->bigInteger('access_hash')->nullable();",
            "    \$table->integer('date')->nullable();",
            "    \$table->text('mime_type')->nullable();",
            "    \$table->bigInteger('size')->nullable();",
            "    \$table->integer('dc_id')->nullable();",
            "    \$table->binary('file_reference')->nullable();",
            "    \$table->jsonb('tl_data');",
            "    \$table->timestamps();",
            "    \$table->primary(['id', 'account_id']);",
            "    \$table->index('account_id', 'ix_tf_documents_account_id');",
        ];
    }

    /** @return list<string> */
    private static function ddlPhotos(): array
    {
        return [
            "    \$table->bigInteger('id');",
            "    \$table->bigInteger('constructor_id');",
            "    \$table->bigInteger('account_id');",
            "    \$table->bigInteger('access_hash')->nullable();",
            "    \$table->integer('date')->nullable();",
            "    \$table->integer('dc_id')->nullable();",
            "    \$table->boolean('has_stickers')->default(false);",
            "    \$table->binary('file_reference')->nullable();",
            "    \$table->jsonb('tl_data');",
            "    \$table->timestamps();",
            "    \$table->primary(['id', 'account_id']);",
            "    \$table->index('account_id', 'ix_tf_photos_account_id');",
        ];
    }

    /** @return list<string> */
    private static function ddlStickerSets(): array
    {
        return [
            "    \$table->bigInteger('id');",
            "    \$table->bigInteger('constructor_id');",
            "    \$table->bigInteger('account_id');",
            "    \$table->bigInteger('access_hash')->nullable();",
            "    \$table->text('title')->nullable();",
            "    \$table->text('short_name')->nullable();",
            "    \$table->integer('count')->nullable();",
            "    \$table->jsonb('hashes')->nullable();",
            "    \$table->jsonb('tl_data');",
            "    \$table->timestamps();",
            "    \$table->primary(['id', 'account_id']);",
            "    \$table->index('account_id', 'ix_tf_sticker_sets_account_id');",
        ];
    }

    /** @return list<string> */
    private static function ddlStories(): array
    {
        return [
            "    \$table->bigInteger('id');",
            "    \$table->primary('id');",
            "    \$table->integer('story_id');",
            "    \$table->bigInteger('peer_id');",
            "    \$table->bigInteger('constructor_id');",
            "    \$table->bigInteger('account_id');",
            "    \$table->integer('date')->nullable();",
            "    \$table->integer('expire_date')->nullable();",
            "    \$table->text('caption')->nullable();",
            "    \$table->jsonb('tl_data');",
            "    \$table->timestamps();",
            "    \$table->unique(['peer_id', 'story_id', 'account_id'], 'ux_tf_stories_scope');",
            "    \$table->index('account_id', 'ix_tf_stories_account_id');",
        ];
    }

    /** @return list<string> */
    private static function ddlWallpapers(): array
    {
        return [
            "    \$table->bigInteger('id');",
            "    \$table->bigInteger('constructor_id');",
            "    \$table->bigInteger('account_id');",
            "    \$table->bigInteger('access_hash')->nullable();",
            "    \$table->text('title')->nullable();",
            "    \$table->text('slug')->nullable();",
            "    \$table->bigInteger('document_id')->nullable();",
            "    \$table->jsonb('tl_data');",
            "    \$table->timestamps();",
            "    \$table->primary(['id', 'account_id']);",
            "    \$table->index('account_id', 'ix_tf_wallpapers_account_id');",
        ];
    }

    /** @return list<string> */
    private static function ddlChannelParticipants(): array
    {
        return [
            "    \$table->bigInteger('id');",
            "    \$table->primary('id');",
            "    \$table->bigInteger('channel_id');",
            "    \$table->bigInteger('user_id');",
            "    \$table->bigInteger('constructor_id');",
            "    \$table->bigInteger('account_id');",
            "    \$table->integer('date')->nullable();",
            "    \$table->jsonb('tl_data');",
            "    \$table->timestamps();",
            "    \$table->unique(['channel_id', 'user_id', 'account_id'], 'ux_tf_ch_participants_scope');",
            "    \$table->index('account_id', 'ix_tf_ch_participants_account_id');",
        ];
    }

    // ── Route table (unchanged from original) ────────────────────────

    private function routeMigration(TlScheme $scheme): string
    {
        $up = [];
        $down = [];
        $methods = $scheme->methods();
        ksort($methods);
        foreach ($methods as $method) {
            $ret = $method->returnType;
            if ($ret === 'X' || str_contains($ret, '<') || $ret === 'Vector t') {
                continue;
            }
            $route = 'tl_route_' . Naming::snake($method->name);
            $this->currentTable = $route;
            $this->tableMap[$route] = $this->currentFile;
            $up[] = "Schema::create('{$route}', function (Blueprint \$table) {";
            $up[] = "    \$table->bigIncrements('id');";
            $up[] = "    \$table->string('route_id', 36)->unique();";
            $up[] = "    \$table->timestamps();";
            $up[] = "});";
            $down[] = "Schema::dropIfExists('{$route}');";
        }
        return CodeWriter::migrationFile($up, array_reverse($down));
    }
}
