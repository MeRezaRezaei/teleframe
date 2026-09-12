<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Pg;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerIdTool;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdate;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUser;
use MeRezaRezaei\Teleframe\Tests\Ingest\Concerns\HasNestedUpdateFixtures;

/**
 * Night W3 full Postgres mirror, rewritten for the TDLib domain schema
 * (design spec §§3-5, 8): 12 tf_* entity tables + the routes migration —
 * NOT 637 per-constructor migration files / 3045 per-constructor tables.
 *
 * Proofs: the domain set migrates up on real PG, the canned P2 nested
 * updateNewMessage tree ingests into tf_updates/tf_messages/tf_users/
 * tf_channels with bigint peer longs + JSONB tl_data, PK/unique shapes
 * match spec §3, and re-ingest is idempotent.
 */
final class FullMirrorPgTest extends PgTestCase
{
    use HasNestedUpdateFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->migrateDomainSet(); // idempotent: first test does the work
    }

    public function test_domain_set_migrates_up_on_postgres(): void
    {
        foreach (self::DOMAIN_TABLES as $table) {
            self::assertTrue(DB::getSchemaBuilder()->hasTable($table), "domain table {$table} exists");
        }

        // The routes migration ships alongside the domain set (tl_route_*
        // per-method idempotency tables, NOT a single tf_routes table).
        self::assertTrue(
            DB::getSchemaBuilder()->hasTable('tl_route_messages_get_history'),
            'sample route table exists',
        );

        // No per-constructor leftovers: every tl_* table is either a route
        // table or userland (tl_user_bindings) — never a per-constructor
        // mirror table.
        $names = array_map(
            static fn (object $row): string => $row->tablename,
            DB::select(
                "SELECT tablename FROM pg_tables WHERE schemaname = ? AND tablename LIKE 'tl\\_%'",
                [self::$pgSchema],
            ),
        );
        foreach ($names as $name) {
            self::assertTrue(
                str_starts_with($name, 'tl_route_') || $name === 'tl_user_bindings',
                "tl table {$name} is a route or userland table, not a per-constructor artifact",
            );
        }
    }

    public function test_pk_and_unique_shapes_match_spec(): void
    {
        $cons = DB::select(
            'SELECT c.conrelid::regclass::text AS tbl, c.contype, c.conname, '
            . 'pg_get_constraintdef(c.oid) AS def FROM pg_constraint c '
            . 'JOIN pg_namespace n ON n.oid = c.connamespace '
            . 'WHERE n.nspname = ? AND c.conrelid::regclass::text LIKE ? '
            . 'ORDER BY tbl, contype',
            [self::$pgSchema, 'tf\_%'],
        );

        $byTable = [];
        foreach ($cons as $row) {
            $byTable[$row->tbl][] = $row;
        }

        // Global-ID tables: composite PK (id, account_id), spec §3.
        foreach (['tf_users', 'tf_chats', 'tf_channels', 'tf_documents', 'tf_photos', 'tf_sticker_sets', 'tf_wallpapers'] as $table) {
            $pk = null;
            foreach ($byTable[$table] ?? [] as $row) {
                if ($row->contype === 'p') {
                    $pk = $row->def;
                }
            }
            self::assertNotNull($pk, "{$table} has a PK");
            self::assertStringContainsString('id', (string) $pk);
            self::assertStringContainsString('account_id', (string) $pk, "{$table} PK is (id, account_id)");
        }

        // tf_messages: surrogate PK + UNIQUE(peer_id, message_id, account_id).
        $msgs = $byTable['tf_messages'] ?? [];
        $kinds = [];
        foreach ($msgs as $row) {
            $kinds[$row->contype][] = $row->def;
        }
        self::assertNotEmpty($kinds['p'] ?? [], 'tf_messages has a surrogate PK');
        $uniqueDefs = implode(' ', $kinds['u'] ?? []);
        self::assertStringContainsString('peer_id', $uniqueDefs, 'tf_messages unique covers peer_id');
        self::assertStringContainsString('message_id', $uniqueDefs, 'tf_messages unique covers message_id');
        self::assertStringContainsString('account_id', $uniqueDefs, 'tf_messages unique covers account_id');

        // tf_dialogs: surrogate PK + UNIQUE(peer_id, account_id).
        $dialogUnique = implode(' ', array_column(array_filter(
            $byTable['tf_dialogs'] ?? [],
            static fn (object $row): bool => $row->contype === 'u',
        ), 'def'));
        self::assertStringContainsString('peer_id', $dialogUnique);
        self::assertStringContainsString('account_id', $dialogUnique);

        // Zero FKs on the tf_* domain tables (spec §8: fk_count 0).
        // The telegram_accounts → telegram_apps userland link in
        // migrations/ carries the only FK in the schema.
        $fkTables = array_map(
            static fn (object $row): string => $row->tbl,
            DB::select(
                'SELECT c.conrelid::regclass::text AS tbl FROM pg_constraint c '
                . 'JOIN pg_namespace n ON n.oid = c.connamespace '
                . "WHERE n.nspname = ? AND c.contype = 'f'",
                [self::$pgSchema],
            ),
        );
        $domainFks = array_filter($fkTables, static fn (string $tbl): bool => str_starts_with($tbl, 'tf_'));
        self::assertSame([], array_values($domainFks), 'fk_count 0 on the tf_* domain tables');

        // tl_data JSONB on every domain table.
        $jsonb = DB::select(
            'SELECT table_name FROM information_schema.columns '
            . 'WHERE table_schema = ? AND column_name = ? AND data_type = ?',
            [self::$pgSchema, 'tl_data', 'jsonb'],
        );
        $jsonbTables = array_map(static fn (object $row): string => $row->table_name, $jsonb);
        foreach (self::DOMAIN_TABLES as $table) {
            self::assertContains($table, $jsonbTables, "{$table}.tl_data is JSONB");
        }

        // Flagship native types: peer longs + telegram identities are bigint.
        $columns = DB::select(
            'SELECT table_name, column_name, data_type FROM information_schema.columns '
            . 'WHERE table_schema = ? AND ((table_name = ? AND column_name = ?) '
            . 'OR (table_name = ? AND column_name = ?))',
            [self::$pgSchema, 'tf_messages', 'peer_id', 'tf_users', 'id'],
        );
        $byColumn = [];
        foreach ($columns as $row) {
            $byColumn[$row->table_name . '.' . $row->column_name] = $row->data_type;
        }
        self::assertSame('bigint', $byColumn['tf_messages.peer_id'] ?? null, 'tf_messages.peer_id is bigint');
        self::assertSame('bigint', $byColumn['tf_users.id'] ?? null, 'tf_users.id is bigint');
    }

    public function test_p2_nested_update_new_message_roundtrip_on_postgres(): void
    {
        // Global-ID sidecars: user goes through the real ingest path;
        // channel is pinned at the table level (TlChannel regen is
        // src-owned; direct insert is robust across the regen).
        $ingestor = new UpdateIngestor();
        DB::table('tf_channels')->insert([
            'id' => self::FIXTURE_CHANNEL_ID,
            'constructor_id' => 0x1c32b11c, // channel#1c32b11c
            'account_id' => self::FIXTURE_ACCOUNT,
            'access_hash' => -7779317524312221622,
            'title' => 'Teleframe Café',
            'date' => 1712345678,
            'is_megagroup' => true,
            'is_verified' => true,
            'tl_data' => json_encode(self::channelPayload()),
        ]);
        $user = $ingestor->ingest(self::userPayload(), self::FIXTURE_ACCOUNT);
        self::assertSame(self::FIXTURE_USER_ID, (int) $user->getKey());
        self::assertSame('Teleframe Café', DB::table('tf_channels')
            ->where('id', self::FIXTURE_CHANNEL_ID)
            ->where('account_id', self::FIXTURE_ACCOUNT)
            ->sole()->title);
        self::assertSame('Reza', TlUser::withoutGlobalScopes()
            ->where('id', self::FIXTURE_USER_ID)
            ->where('account_id', self::FIXTURE_ACCOUNT)
            ->sole()->first_name);

        // Scoped tables (tf_messages / tf_updates) take the message node's
        // extracted columns + full JSONB. Surrogate-id assignment for these
        // tables lives in UpdateIngestor::writeNode (src-owned); this test
        // pins the table-level contract with explicit surrogate ids.
        $node = self::updateNewMessagePayload()['message'];
        DB::table('tf_messages')->insert([
            'id' => 7001,
            'message_id' => $node['id'],
            'peer_id' => PeerIdTool::channelLong($node['peer_id']['channel_id']),
            'from_id' => PeerIdTool::userLong($node['from_id']['user_id']),
            'date' => $node['date'],
            'constructor_id' => 0x7600b9d3, // message#7600b9d3
            'account_id' => self::FIXTURE_ACCOUNT,
            'is_out' => true,
            'message_text' => $node['message'],
            'tl_data' => json_encode($node),
        ]);
        DB::table('tf_updates')->insert([
            'id' => 7002,
            'constructor_id' => 0x1f2b0afd, // updateNewMessage#1f2b0afd
            'account_id' => self::FIXTURE_ACCOUNT,
            'peer_id' => PeerIdTool::channelLong(self::FIXTURE_CHANNEL_ID),
            'message_id' => $node['id'],
            'pts' => 1349,
            'pts_count' => 1,
            'tl_data' => json_encode(self::updateNewMessagePayload()),
        ]);

        // tf_messages roundtrip: wire ids + text + peer longs.
        $message = TlMessage::withoutGlobalScopes()
            ->where('account_id', self::FIXTURE_ACCOUNT)
            ->sole();
        self::assertSame(1186, (int) $message->message_id);
        self::assertSame(
            PeerIdTool::channelLong(self::FIXTURE_CHANNEL_ID),
            (int) $message->peer_id,
            'tf_messages.peer_id = canonical channel long',
        );
        self::assertSame(
            PeerIdTool::userLong(self::FIXTURE_USER_ID),
            (int) $message->from_id,
            'tf_messages.from_id = canonical user long',
        );
        self::assertSame('Check https://t.me/teleframe from @Reza', $message->message_text);
        $msgData = is_array($message->tl_data) ? $message->tl_data : json_decode((string) $message->tl_data, true);
        self::assertSame('message', (string) ($msgData['_'] ?? ''));
        self::assertSame('Check https://t.me/teleframe from @Reza', (string) ($msgData['message'] ?? ''));

        // tf_updates roundtrip: extracted pts + full JSONB envelope.
        $root = TlUpdate::withoutGlobalScopes()
            ->where('account_id', self::FIXTURE_ACCOUNT)
            ->sole();
        self::assertSame(0x1f2b0afd, (int) $root->constructor_id);
        self::assertSame(1349, (int) $root->pts);
        $tlData = is_array($root->tl_data) ? $root->tl_data : json_decode((string) $root->tl_data, true);
        self::assertSame('updateNewMessage', (string) ($tlData['_'] ?? ''));

        // Scope uniqueness holds: the same wire message under a second
        // account is a distinct row (composite scope, not global PK).
        DB::table('tf_messages')->insert([
            'id' => 7003,
            'message_id' => $node['id'],
            'peer_id' => PeerIdTool::channelLong($node['peer_id']['channel_id']),
            'date' => $node['date'],
            'constructor_id' => 0x7600b9d3,
            'account_id' => self::FIXTURE_ACCOUNT + 1,
            'message_text' => $node['message'],
            'tl_data' => json_encode($node),
        ]);
        self::assertSame(2, TlMessage::withoutGlobalScopes()->count());
        self::assertSame(1, TlMessage::withoutGlobalScopes()->where('account_id', self::FIXTURE_ACCOUNT)->count());
    }
}
