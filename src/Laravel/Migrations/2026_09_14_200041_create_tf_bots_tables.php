<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NF5 mirror — stars/business domain part 2 (plan Task 6): attach menu bots,
 * bot apps, bot infos, bot inline results.
 *
 * Hand-authored against TL_telegram_v227.tl + the 2026-09-11 mirror catalog:
 *
 * - tf_attach_menu_bots   — single-ctor attachMenuBot; keyed by the natural
 *   bot_id (catalog `base`). icons (1:N AttachMenuBotIcon) and peer_types
 *   (1:N AttachMenuPeerType) are vector children; the icon:Document and
 *   colors payloads of attachMenuBotIcon are nested objects → deferred.
 * - tf_bot_apps           — two-ctor union (botAppNotModified | botApp) →
 *   constructor discriminator; every botApp scalar type-defaults so the empty
 *   botAppNotModified row places. photo:F Photo / document:flags.0?Document →
 *   flattened Photo/Document union children; their inner `id` is dropped (the
 *   child id column is the bot-app key — plain-name collision under the flat
 *   path, same ruling as tf_messages_media).
 * - tf_bot_infos          — single-ctor botInfo that carries NO own id; the
 *   parent key id is the bot's user id (designer key). base is empty: every
 *   payload field is flag-gated, so all nine facts are 1:1/1:N children.
 * - tf_bot_inline_results — two-ctor union (botInlineResult |
 *   botInlineMediaResult) → constructor discriminator; id is the TL `string`.
 *   send_message (required BotInlineMessage union) → flattened 1:1 child.
 *
 * Shared flatten rules (same as the messages/media/stars domains):
 * - flags.?true → BOOLEAN NOT NULL DEFAULT FALSE (catalog `bools`).
 * - Optional/flag-gated scalar or object facts → 1:1 child (row exists = fact
 *   exists; union object children carry their own constructor column).
 * - Vector<X> → 1:N child keyed (account_id, key[, position]).
 * - bytes → lowercase hex TEXT. No nullable, no json/blob, no auto-increment,
 *   no timestamps.
 * - Nested objects under a flattened union (Document/Photo attributes, thumb
 *   vectors, ReplyMarkup, Vector<MessageEntity>, GeoPoint, WebDocument, ...)
 *   cannot nest under the flat decomposer write path → deferred; the
 *   discriminator + immediate flat fields are the mirror truth here.
 * - No FK constraints are declared (messages-domain style): cross-domain and
 *   intra-domain FK wiring is the Task-8 299999 migration after the worktrees
 *   merge. Eloquent relations carry the graph meanwhile.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->createAttachMenuBots();
        $this->createBotApps();
        $this->createBotInfos();
        $this->createBotInlineResults();
    }

    public function down(): void
    {
        foreach (array_reverse(self::INLINE_RESULT_CHILDREN) as $t) {
            Schema::dropIfExists($t);
        }
        Schema::dropIfExists('tf_bot_inline_results');

        foreach (array_reverse(self::BOT_INFO_CHILDREN) as $t) {
            Schema::dropIfExists($t);
        }
        Schema::dropIfExists('tf_bot_infos');

        foreach (array_reverse(self::BOT_APP_CHILDREN) as $t) {
            Schema::dropIfExists($t);
        }
        Schema::dropIfExists('tf_bot_apps');

        foreach (array_reverse(self::ATTACH_MENU_BOT_CHILDREN) as $t) {
            Schema::dropIfExists($t);
        }
        Schema::dropIfExists('tf_attach_menu_bots');
    }

    private const ATTACH_MENU_BOT_CHILDREN = [
        'tf_attach_menu_bots_icons',
        'tf_attach_menu_bots_peer_types',
    ];

    private const BOT_APP_CHILDREN = [
        'tf_bot_apps_photo',
        'tf_bot_apps_document',
    ];

    private const BOT_INFO_CHILDREN = [
        'tf_bot_infos_user_id',
        'tf_bot_infos_description',
        'tf_bot_infos_description_photo',
        'tf_bot_infos_description_document',
        'tf_bot_infos_commands',
        'tf_bot_infos_menu_button',
        'tf_bot_infos_privacy_policy_url',
        'tf_bot_infos_app_settings',
        'tf_bot_infos_verifier_settings',
    ];

    private const INLINE_RESULT_CHILDREN = [
        'tf_bot_inline_results_send_message',
        'tf_bot_inline_results_title',
        'tf_bot_inline_results_description',
        'tf_bot_inline_results_url',
        'tf_bot_inline_results_thumb',
        'tf_bot_inline_results_content',
    ];

    private function createAttachMenuBots(): void
    {
        Schema::create('tf_attach_menu_bots', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('bot_id');
            $table->text('short_name');
            $table->boolean('inactive')->default(false);
            $table->boolean('has_settings')->default(false);
            $table->boolean('request_write_access')->default(false);
            $table->boolean('show_in_attach_menu')->default(false);
            $table->boolean('show_in_side_menu')->default(false);
            $table->boolean('side_menu_disclaimer_needed')->default(false);

            $table->primary(['account_id', 'bot_id']);
        });

        // icons — Vector<AttachMenuBotIcon> (single ctor). The required
        // Document `icon` and Vector<AttachMenuBotIconColor> `colors` are
        // nested objects → deferred; the immediate `name` is the mirror truth.
        Schema::create('tf_attach_menu_bots_icons', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('bot_id');
            $table->smallInteger('position');
            $table->text('name');
            $table->primary(['account_id', 'bot_id', 'position']);
            $table->index(['account_id', 'bot_id']);
        });

        // peer_types — Vector<AttachMenuPeerType>: five payload-less ctors,
        // constructor discriminator only.
        Schema::create('tf_attach_menu_bots_peer_types', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('bot_id');
            $table->smallInteger('position');
            $table->string('constructor', 64);
            $table->primary(['account_id', 'bot_id', 'position']);
            $table->index(['account_id', 'bot_id']);
        });
    }

    private function createBotApps(): void
    {
        Schema::create('tf_bot_apps', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->string('constructor', 64);
            $table->bigInteger('id');
            $table->bigInteger('access_hash')->default(0);
            $table->text('short_name')->default('');
            $table->text('title')->default('');
            $table->text('description')->default('');
            $table->bigInteger('hash')->default(0);

            $table->primary(['account_id', 'id']);
        });

        // photo — REQUIRED Photo union (photoEmpty | photo), flattened;
        // the photo's inner `id` collides with the parent key → dropped.
        Schema::create('tf_bot_apps_photo', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            self::photoUnionColumns($table);
            $table->primary(['account_id', 'id']);
        });

        // document — flags.0?Document union (documentEmpty | document),
        // flattened; the document's inner `id` collides → dropped.
        Schema::create('tf_bot_apps_document', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            self::documentUnionColumns($table);
            $table->primary(['account_id', 'id']);
        });
    }

    private function createBotInfos(): void
    {
        Schema::create('tf_bot_infos', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->boolean('has_preview_medias')->default(false);

            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_bot_infos_user_id', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->bigInteger('user_id');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_bot_infos_description', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->text('description');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_bot_infos_description_photo', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            self::photoUnionColumns($table);
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_bot_infos_description_document', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            self::documentUnionColumns($table);
            $table->primary(['account_id', 'id']);
        });

        // commands — required Vector<BotCommand> (single-ctor botCommand).
        Schema::create('tf_bot_infos_commands', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->smallInteger('position');
            $table->text('command');
            $table->text('description');
            $table->primary(['account_id', 'id', 'position']);
            $table->index(['account_id', 'id']);
        });

        // menu_button — BotMenuButton union: botMenuButtonDefault /
        // botMenuButtonCommands / botMenuButton (text + url).
        Schema::create('tf_bot_infos_menu_button', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->string('constructor', 64);
            $table->text('text')->default('');
            $table->text('url')->default('');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_bot_infos_privacy_policy_url', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->text('privacy_policy_url');
            $table->primary(['account_id', 'id']);
        });

        // app_settings — single-ctor botAppSettings; placeholder_path bytes →
        // lowercase hex TEXT.
        Schema::create('tf_bot_infos_app_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->text('placeholder_path')->default('');
            $table->integer('background_color')->default(0);
            $table->integer('background_dark_color')->default(0);
            $table->integer('header_color')->default(0);
            $table->integer('header_dark_color')->default(0);
            $table->primary(['account_id', 'id']);
        });

        // verifier_settings — single-ctor botVerifierSettings.
        Schema::create('tf_bot_infos_verifier_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->boolean('can_modify_custom_description')->default(false);
            $table->bigInteger('icon')->default(0);
            $table->text('company')->default('');
            $table->text('custom_description')->default('');
            $table->primary(['account_id', 'id']);
        });
    }

    private function createBotInlineResults(): void
    {
        Schema::create('tf_bot_inline_results', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->string('constructor', 64);
            $table->text('id');
            $table->text('type');
            $table->primary(['account_id', 'id']);
        });

        // send_message — REQUIRED BotInlineMessage union (9 ctors), flattened;
        // geo/entities/reply_markup/photo/rich_message nested objects deferred.
        Schema::create('tf_bot_inline_results_send_message', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->text('id');
            self::botInlineMessageColumns($table);
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_bot_inline_results_title', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->text('id');
            $table->text('title');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_bot_inline_results_description', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->text('id');
            $table->text('description');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_bot_inline_results_url', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->text('id');
            $table->text('url');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_bot_inline_results_thumb', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->text('id');
            self::webDocumentColumns($table);
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_bot_inline_results_content', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->text('id');
            self::webDocumentColumns($table);
            $table->primary(['account_id', 'id']);
        });
    }

    private static function photoUnionColumns(Blueprint $table): void
    {
        $table->string('constructor', 64);
        $table->bigInteger('access_hash')->default(0);
        $table->text('file_reference')->default('');
        $table->integer('date')->default(0);
        $table->integer('dc_id')->default(0);
        $table->boolean('has_stickers')->default(false);
    }

    private static function documentUnionColumns(Blueprint $table): void
    {
        $table->string('constructor', 64);
        $table->bigInteger('access_hash')->default(0);
        $table->text('file_reference')->default('');
        $table->integer('date')->default(0);
        $table->text('mime_type')->default('');
        $table->bigInteger('size')->default(0);
        $table->integer('dc_id')->default(0);
    }

    private static function webDocumentColumns(Blueprint $table): void
    {
        $table->string('constructor', 64);
        $table->text('url');
        $table->bigInteger('access_hash')->default(0);
        $table->integer('size')->default(0);
        $table->text('mime_type')->default('');
    }

    /**
     * Flat BotInlineMessage union column set (9 ctors). Shared flag/scalar
     * residue only — GeoPoint (geo), ReplyMarkup, Vector<MessageEntity>, the
     * invoice Photo (WebDocument) and RichMessage are nested → deferred.
     */
    private static function botInlineMessageColumns(Blueprint $table): void
    {
        $table->string('constructor', 64);
        $table->boolean('no_webpage')->default(false);
        $table->boolean('invert_media')->default(false);
        $table->boolean('shipping_address_requested')->default(false);
        $table->boolean('test')->default(false);
        $table->boolean('force_large_media')->default(false);
        $table->boolean('force_small_media')->default(false);
        $table->boolean('manual')->default(false);
        $table->boolean('safe')->default(false);
        $table->text('message')->default('');
        $table->text('title')->default('');
        $table->text('description')->default('');
        $table->text('url')->default('');
        $table->text('phone_number')->default('');
        $table->text('first_name')->default('');
        $table->text('last_name')->default('');
        $table->text('vcard')->default('');
        $table->text('address')->default('');
        $table->text('provider')->default('');
        $table->text('venue_id')->default('');
        $table->text('venue_type')->default('');
        $table->text('currency')->default('');
        $table->bigInteger('total_amount')->default(0);
        $table->integer('heading')->default(0);
        $table->integer('period')->default(0);
        $table->integer('proximity_notification_radius')->default(0);
    }
};
