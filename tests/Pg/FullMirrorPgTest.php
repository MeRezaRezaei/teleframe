<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Pg;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\EntityAggregator;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerIdTool;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChat;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatChannel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMessageEntities;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPeer;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPeerPeerChannel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPeerPeerUser;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdate;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateNewMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUser;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUserUser;
use MeRezaRezaei\Teleframe\Tests\Ingest\Concerns\HasNestedUpdateFixtures;

/**
 * Night W3 full Postgres mirror: the ENTIRE generated migration set
 * (637 files — sqlite tests only ever ran the 112-file dial + entity
 * anchors) migrates up on real PG 17, then the canned P2 nested
 * updateNewMessage ingest and the entity-aggregator read path roundtrip
 * against it with the same invariants the sqlite suite asserts.
 */
final class FullMirrorPgTest extends PgTestCase
{
    use HasNestedUpdateFixtures;

    /** Files in generated/migrations (schema-manifest parity: 637). */
    private const FULL_SET_FILES = 637;

    /** Schema::create calls across the full set (3045 user tables + migrations bookkeeping). */
    private const FULL_SET_TABLES = 3045;

    protected function setUp(): void
    {
        parent::setUp();
        $this->migrateFullGeneratedSet(); // idempotent: first test does the work
    }

    public function test_full_generated_set_migrates_up_on_postgres(): void
    {
        // All 637 migration files recorded as run…
        self::assertSame(self::FULL_SET_FILES, (int) DB::table('migrations')->count());

        // …and the schema carries exactly the expected tables: the 3045
        // generated tables plus the migrations bookkeeping table.
        self::assertSame(self::FULL_SET_TABLES + 1, $this->pgTableCount());
        self::assertSame(self::FULL_SET_TABLES, (int) DB::selectOne(
            "SELECT count(*) AS n FROM pg_tables WHERE schemaname = ? AND tablename <> 'migrations'",
            [self::$pgSchema],
        )?->n);

        // Flagship members of every band: anchor, instance, child, route
        // (methods keep TL namespaces: messages.sendMessage), and the
        // bucketed deferrable-FK artifacts.
        foreach (['tl_user_user', 'tl_message_message', 'tl_message_message__entities', 'tl_route_messages_send_message', 'tl_update_update_new_message'] as $table) {
            self::assertTrue(DB::getSchemaBuilder()->hasTable($table), "table {$table} exists");
        }
        self::assertNotEmpty(array_filter(glob(dirname(__DIR__, 2) . '/generated/migrations/*_add_tl_foreign_keys.php') ?: [], 'is_file'), 'bucketed FK migrations present');
    }

    public function test_native_pk_columns_and_identity_precision(): void
    {
        // What sqlite hides as TEXT: PG carries native bigint PKs (surrogate
        // serial bigint for every constructor table) and keeps crc32
        // constructor ids and 64-bit telegram ids as bigint.
        $columns = DB::select(
            'SELECT table_name, column_name, data_type FROM information_schema.columns '
            . 'WHERE table_schema = ? AND ((table_name = ? AND column_name = ?) '
            . 'OR (table_name = ? AND column_name = ?) '
            . 'OR (table_name = ? AND column_name = ?))',
            [self::$pgSchema, 'tl_user_user', 'id', 'tl_user_user', 'constructor_id', 'tl_peer_peer_user', 'user_id'],
        );
        $byColumn = [];
        foreach ($columns as $row) {
            $byColumn[$row->table_name . '.' . $row->column_name] = $row->data_type;
        }

        self::assertSame('bigint', $byColumn['tl_user_user.id'] ?? null, 'every constructor PK is a native bigint');
        self::assertSame('bigint', $byColumn['tl_user_user.constructor_id'] ?? null, 'crc32 constructor id stays bigint');
        self::assertSame('bigint', $byColumn['tl_peer_peer_user.user_id'] ?? null, 'telegram identity stays bigint');
    }

    public function test_p2_nested_update_new_message_roundtrip_on_postgres(): void
    {
        $ingestor = new UpdateIngestor();
        $ingestor->ingest(self::channelPayload(), self::FIXTURE_ACCOUNT);
        $ingestor->ingest(self::userPayload(), self::FIXTURE_ACCOUNT);
        $root = $ingestor->ingest(self::updateNewMessagePayload(), self::FIXTURE_ACCOUNT);

        // Root anchor + instance: update namespace, tenant, verbatim pts cols.
        self::assertInstanceOf(TlUpdateUpdateNewMessage::class, $root);
        $anchor = TlUpdate::query()->sole();
        self::assertIsInt((int) $anchor->id, 'integer PK survives the PG roundtrip');
        self::assertSame(0x1f2b0afd, $anchor->constructor_id);
        self::assertSame('updateNewMessage', $anchor->constructor_name);
        self::assertSame(self::FIXTURE_ACCOUNT, (int) $anchor->account_id);
        self::assertSame(1349, $root->pts);

        // Message namespace: verbatim instance columns, ref columns carry
        // the child instances' shared uuid PKs.
        $message = TlMessageMessage::query()->sole();
        self::assertSame(1186, $message->tl_id);
        self::assertSame('Check https://t.me/teleframe from @Reza', $message->message);
        self::assertSame($root->message, (int) $message->id);
        $fromPeer = TlPeerPeerUser::query()->sole();
        $chanPeer = TlPeerPeerChannel::query()->sole();
        self::assertSame(self::FIXTURE_USER_ID, $fromPeer->user_id);
        self::assertSame(PeerIdTool::userLong(self::FIXTURE_USER_ID), (int) $message->from_id, 'message.from_id = canonical user long (T1.2)');
        self::assertSame(self::FIXTURE_CHANNEL_ID, $chanPeer->channel_id);
        self::assertSame(PeerIdTool::channelLong(self::FIXTURE_CHANNEL_ID), (int) $message->peer_id, 'message.peer_id = canonical channel long (T1.2)');
        self::assertSame(2, TlPeer::query()->count());

        // Entities vector → child rows keep wire order by idx.
        self::assertSame([0, 1, 2], $message->entities()->orderBy('idx')->pluck('idx')->all());
        self::assertSame(3, TlMessageMessageEntities::query()->where('parent_id', $message->id)->count());

        // Aggregator read path: (tenant, telegram id) → anchor with the
        // CURRENT instance — the queries hosts actually run.
        $user = (new EntityAggregator())->user(self::FIXTURE_ACCOUNT, self::FIXTURE_USER_ID);
        self::assertInstanceOf(TlUserUser::class, $user?->currentInstance);
        self::assertSame('Reza', $user->currentInstance->first_name);
        $chat = (new EntityAggregator())->chat(self::FIXTURE_ACCOUNT, self::FIXTURE_CHANNEL_ID);
        self::assertInstanceOf(TlChatChannel::class, $chat?->currentInstance);
        self::assertSame('Teleframe Café', $chat->currentInstance->title);
        self::assertInstanceOf(TlChat::class, $chat);
        self::assertInstanceOf(TlUser::class, $user);

        // Idempotent re-ingest: counts stay stable on PG too.
        $again = (new UpdateIngestor())->ingest(self::updateNewMessagePayload(), self::FIXTURE_ACCOUNT);
        self::assertSame((string) $root->id, (string) $again->id, 'root anchor reused');
        self::assertSame(1, TlUpdate::query()->count());
        self::assertSame(1, TlMessageMessage::query()->count());
        self::assertSame(2, TlPeer::query()->count());
    }
}
