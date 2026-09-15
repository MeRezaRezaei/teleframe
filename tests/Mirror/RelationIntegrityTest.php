<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Mirror;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use MeRezaRezaei\Teleframe\Mirror\Models\TfChannel;
use MeRezaRezaei\Teleframe\Mirror\Models\TfChannelParticipant;
use MeRezaRezaei\Teleframe\Mirror\Models\TfMessage;
use MeRezaRezaei\Teleframe\Mirror\Models\TfMessageEntity;
use MeRezaRezaei\Teleframe\Mirror\Models\TfMessageMedia;
use MeRezaRezaei\Teleframe\Tests\Ingest\IngestTestCase;

/**
 * Cross-domain FK relation-completeness proof (plan Task 8).
 *
 * The last migration in the curated dial (2026_09_14_299999) is the single
 * place where FKs that cross curated-domain boundaries live. Every relation
 * the curated models document across tables must be backed by a real FK in
 * that migration — and every FK it declares must be accounted for here.
 *
 * Verified against the ACTUAL curated migrations (not the plan's working
 * column names — several differ):
 *  - tf_messages_media / tf_messages_entities carry the parent message key
 *    as (account_id, id) with NO separate message_id column;
 *  - the polymorphic peer pair (peer_type/peer_id) on tf_messages / tf_dialogs
 *    and the media→documents/photos refs from the plan are NOT physically
 *    constraining (one FK cannot branch on peer_type; the curated media table
 *    has no document/photo FK columns) — enforced in app code instead, see the
 *    299999 migration docblock.
 *
 * Runs the full UpdateIngestor::migrationPaths() dial on sqlite :memory: with
 * PRAGMA foreign_keys = ON, so the FK proof is behavioral, not source-parsed.
 */
final class RelationIntegrityTest extends IngestTestCase
{
    private const ACCOUNT = 41;

    private const MIGRATION_FILE = '2026_09_14_299999_create_tf_foreign_keys.php';

    /**
     * The promised cross-domain FKs: child table → FK columns → parent table
     * → on-delete action. `model` / `relation` name the Eloquent relation that
     * must exist on the model side; null means the FK is a canonical peer ref
     * with no modeled belongsTo (the participant keeps its composite-key child
     * accessors instead — see TfChannelParticipant).
     *
     * @var list<array{child:string, fk:list<string>, parent:string, action:string, model?:class-string, relation?:string}>
     */
    private const CROSS_DOMAIN_FKS = [
        // message 1:1 media child → content message (child→parent cascade).
        ['child' => 'tf_messages_media', 'fk' => ['account_id', 'id'], 'parent' => 'tf_messages', 'action' => 'CASCADE', 'model' => TfMessage::class, 'relation' => 'media'],
        // message 1:N entities vector child → content message (child→parent cascade).
        ['child' => 'tf_messages_entities', 'fk' => ['account_id', 'id'], 'parent' => 'tf_messages', 'action' => 'CASCADE', 'model' => TfMessage::class, 'relation' => 'entities'],
        // channel participant → channel (canonical peer ref, RESTRICT).
        ['child' => 'tf_channel_participants', 'fk' => ['account_id', 'channel_id'], 'parent' => 'tf_channels', 'action' => 'RESTRICT'],
    ];

    private const BELONGS_TO_SIDE = [
        TfMessageMedia::class => [TfMessage::class, 'message'],
        TfMessageEntity::class => [TfMessage::class, 'message'],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // FK enforcement on for the whole class: every assertion in here is a
        // behavioral FK proof, not a source grep.
        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function test_migration_is_in_the_curated_ship_dial(): void
    {
        self::assertFileExists(
            dirname(__DIR__, 2).'/src/Laravel/Migrations/'.self::MIGRATION_FILE,
            self::MIGRATION_FILE.' must be shipped in src/Laravel/Migrations',
        );

        $applied = DB::table('migrations')->pluck('migration')->map(
            static fn (string $row): bool => str_contains($row, 'create_tf_foreign_keys'),
        );
        self::assertContains(true, $applied, 'the FK migration must be applied by the full dial');
    }

    public function test_every_promised_cross_domain_fk_exists_in_the_migrated_schema(): void
    {
        foreach (self::CROSS_DOMAIN_FKS as $expected) {
            $actual = $this->foreignKeys($expected['child']);
            $match = array_values(array_filter($actual, static function (array $fk) use ($expected): bool {
                return $fk['columns'] === $expected['fk']
                    && $fk['foreign_table'] === $expected['parent']
                    && strtoupper((string) $fk['on_delete']) === $expected['action'];
            }));

            self::assertCount(1, $match, sprintf(
                'expected %s (%s) → %s %s; found %s',
                $expected['child'],
                json_encode($expected['fk']),
                $expected['parent'],
                $expected['action'],
                json_encode(array_map(static fn (array $f): string => $f['foreign_table'].':'.$f['on_delete'], $actual)),
            ));
        }
    }

    public function test_no_promised_fk_columns_are_nullable(): void
    {
        foreach (self::CROSS_DOMAIN_FKS as $fk) {
            $nullable = Schema::getColumns($fk['child']);
            foreach ($fk['fk'] as $column) {
                $col = array_values(array_filter($nullable, static fn (array $c): bool => $c['name'] === $column))[0] ?? null;
                self::assertNotNull($col, "{$fk['child']}.{$column} must exist");
                self::assertFalse((bool) $col['nullable'], "{$fk['child']}.{$column} must be NOT NULL");
            }
        }
    }

    public function test_model_relations_resolve_to_the_fk_pairs(): void
    {
        $message = new TfMessage(['account_id' => self::ACCOUNT, 'id' => 5, 'constructor' => 'message']);

        $media = $message->media();
        self::assertInstanceOf(HasOne::class, $media);
        self::assertSame('id', $media->getForeignKeyName(), 'TfMessage::media() foreign key must be the shared message key half');
        self::assertSame('id', $media->getLocalKeyName());
        self::assertSame('tf_messages_media', $media->getRelated()->getTable());

        $entities = $message->entities();
        self::assertInstanceOf(HasMany::class, $entities);
        self::assertSame('id', $entities->getForeignKeyName(), 'TfMessage::entities() foreign key must be the shared message key half');
        self::assertSame('id', $entities->getLocalKeyName());
        self::assertSame('tf_messages_entities', $entities->getRelated()->getTable());

        foreach (self::BELONGS_TO_SIDE as $childClass => [$parentClass, $method]) {
            $child = new $childClass(['account_id' => self::ACCOUNT, 'id' => 5]);
            $relation = $child->{$method}();
            self::assertInstanceOf(BelongsTo::class, $relation, sprintf('%s::%s() must be a belongsTo', $childClass, $method));
            self::assertSame('id', $relation->getForeignKeyName(), sprintf('%s::%s() foreign key must be the shared message key half', $childClass, $method));
            self::assertSame('id', $relation->getOwnerKeyName());
            self::assertTrue(
                str_ends_with((string) $relation->getQualifiedOwnerKeyName(), 'tf_messages'.'.'.'id'),
                sprintf('%s::%s() must resolve the owner on tf_messages', $childClass, $method),
            );
            self::assertSame('tf_messages', (new $parentClass)->getTable(), sprintf('%s::%s() must target TfMessage', $childClass, $method));

            $declared = $this->foreignKeys($child->getTable());
            self::assertCount(1, $declared, sprintf('%s must carry exactly one cross-domain FK', $child->getTable()));
            self::assertContains('id', $declared[0]['columns'], sprintf('%s FK must constrain the relation key half', $child->getTable()));
            self::assertSame('tf_messages', $declared[0]['foreign_table']);
        }
    }

    public function test_no_dangling_relation_targets(): void
    {
        foreach (self::CROSS_DOMAIN_FKS as $fk) {
            self::assertTrue(Schema::hasTable($fk['child']), "{$fk['child']} must exist in the migrated dial");
            self::assertTrue(Schema::hasTable($fk['parent']), "{$fk['parent']} must exist in the migrated dial");
        }

        $message = new TfMessage(['account_id' => self::ACCOUNT, 'id' => 5, 'constructor' => 'message']);
        self::assertInstanceOf(TfMessageMedia::class, $message->media()->getRelated());
        self::assertInstanceOf(TfMessageEntity::class, $message->entities()->getRelated());

        $media = new TfMessageMedia(['account_id' => self::ACCOUNT, 'id' => 5]);
        $entity = new TfMessageEntity(['account_id' => self::ACCOUNT, 'id' => 5]);
        self::assertInstanceOf(TfMessage::class, $media->message()->getRelated());
        self::assertInstanceOf(TfMessage::class, $entity->message()->getRelated());
    }

    public function test_orphan_media_without_message_is_rejected(): void
    {
        $this->expectException(QueryException::class);
        DB::table('tf_messages_media')->insert([
            'account_id' => self::ACCOUNT,
            'id' => 404,
            'constructor' => 'messageMediaEmpty',
        ]);
    }

    public function test_orphan_entity_without_message_is_rejected(): void
    {
        $this->expectException(QueryException::class);
        DB::table('tf_messages_entities')->insert([
            'account_id' => self::ACCOUNT,
            'id' => 404,
            'position' => 0,
            'constructor' => 'messageEntityBold',
            'offset' => 0,
            'length' => 1,
        ]);
    }

    public function test_message_delete_cascades_to_media_and_entity_children(): void
    {
        DB::table('tf_messages')->insert([
            'account_id' => self::ACCOUNT,
            'id' => 77,
            'peer_type' => 1,
            'peer_id' => 501558149,
            'constructor' => 'message',
            'date' => 1726000000,
            'message' => 'cascade me',
        ]);
        DB::table('tf_messages_media')->insert([
            'account_id' => self::ACCOUNT,
            'id' => 77,
            'constructor' => 'messageMediaEmpty',
        ]);
        DB::table('tf_messages_entities')->insert([
            'account_id' => self::ACCOUNT,
            'id' => 77,
            'position' => 0,
            'constructor' => 'messageEntityBold',
            'offset' => 0,
            'length' => 1,
        ]);
        DB::table('tf_messages_entities')->insert([
            'account_id' => self::ACCOUNT,
            'id' => 77,
            'position' => 1,
            'constructor' => 'messageEntityItalic',
            'offset' => 2,
            'length' => 3,
        ]);

        self::assertSame(1, DB::table('tf_messages_media')->count());
        self::assertSame(2, DB::table('tf_messages_entities')->count());

        DB::table('tf_messages')->where('account_id', self::ACCOUNT)->where('id', 77)->delete();

        self::assertSame(0, DB::table('tf_messages')->count());
        self::assertSame(0, DB::table('tf_messages_media')->count(), 'media must cascade with its message');
        self::assertSame(0, DB::table('tf_messages_entities')->count(), 'entities must cascade with their message');
    }

    public function test_participant_orphan_without_channel_is_rejected(): void
    {
        $this->insertAccount();

        $this->expectException(QueryException::class);
        DB::table('tf_channel_participants')->insert([
            'account_id' => self::ACCOUNT,
            'channel_id' => -100777,
            'user_id' => 501558149,
            'constructor' => 'channelParticipant',
            'date' => 1726000000,
        ]);
    }

    public function test_channel_delete_is_restricted_while_participants_exist(): void
    {
        $this->insertAccount();
        $channelId = -1002000111;

        DB::table('tf_channels')->insert([
            'account_id' => self::ACCOUNT,
            'id' => $channelId,
            'constructor' => 'channel',
            'title' => 'Harbor Radio',
        ]);
        DB::table('tf_channel_participants')->insert([
            'account_id' => self::ACCOUNT,
            'channel_id' => $channelId,
            'user_id' => 501558149,
            'constructor' => 'channelParticipant',
            'date' => 1726000000,
        ]);

        try {
            DB::table('tf_channels')->where('account_id', self::ACCOUNT)->where('id', $channelId)->delete();
            self::fail('deleting a channel that still has participants must be rejected');
        } catch (QueryException) {
            // RESTRICT holds — expected.
        }

        DB::table('tf_channel_participants')
            ->where('account_id', self::ACCOUNT)
            ->where('channel_id', $channelId)
            ->delete();
        DB::table('tf_channels')->where('account_id', self::ACCOUNT)->where('id', $channelId)->delete();

        self::assertSame(0, DB::table('tf_channels')->count());
        self::assertSame(0, DB::table('tf_channel_participants')->count());
    }

    public function test_participant_and_channel_model_are_dial_present(): void
    {
        $participant = new TfChannelParticipant([
            'account_id' => self::ACCOUNT,
            'channel_id' => -1,
            'user_id' => 1,
            'constructor' => 'channelParticipant',
        ]);
        self::assertSame('tf_channel_participants', $participant->getTable());
        self::assertSame('tf_channels', (new TfChannel)->getTable());
        self::assertTrue(Schema::hasTable('tf_channels'), 'the canonical peer table the FK leans on must ship');
    }

    private function insertAccount(): void
    {
        DB::table('telegram_accounts')->insert([
            'id' => self::ACCOUNT,
            'label' => 'relation-integrity-'.self::ACCOUNT,
            'type' => 'user',
            'dc_id' => 2,
        ]);
    }

    /**
     * @return list<array{columns:list<string>, foreign_table:string, on_delete:string}>
     */
    private function foreignKeys(string $table): array
    {
        return array_map(
            static fn (array $fk): array => [
                'columns' => $fk['columns'],
                'foreign_table' => (string) $fk['foreign_table'],
                'on_delete' => (string) $fk['on_delete'],
            ],
            Schema::getForeignKeys($table),
        );
    }
}
