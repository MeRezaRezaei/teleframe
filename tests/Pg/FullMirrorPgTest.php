<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Pg;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Mirror\Models\TfChannel;
use MeRezaRezaei\Teleframe\Mirror\Models\TfMessage;
use MeRezaRezaei\Teleframe\Mirror\Models\TfUpdate;
use MeRezaRezaei\Teleframe\Mirror\Models\TfUser;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerShapeTool;
use MeRezaRezaei\Teleframe\Tests\Ingest\Concerns\HasNestedUpdateFixtures;

/**
 * Night W3 full Postgres mirror, rewritten for the curated NF5 dial: the
 * hand-authored src/Laravel/Migrations surface (16 create_tf_* migrations +
 * 5 app-owned + the Task-8 FK migration) — NOT per-constructor mirror files,
 * NOT the legacy TDLib domain set, and NOT tl_data JSONB / constructor_id.
 *
 * Proofs: the curated dial migrates up on real PG, PK/peer/constructor shapes
 * match the NF5 contract (account_id-first composite keys, peer_type/peer_id
 * pairs, `constructor` discriminator, zero jsonb), the canned P2 nested
 * updateNewMessage tree ingests into tf_updates/tf_messages with the peer
 * pair + children, and re-ingest is idempotent + tenant-scoped.
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

        // The 5 app-owned migrations ship alongside the curated dial.
        foreach (['telegram_accounts', 'telegram_apps', 'tg_update_routing', 'tl_user_bindings'] as $table) {
            self::assertTrue(DB::getSchemaBuilder()->hasTable($table), "app-owned table {$table} exists");
        }

        // Task 8 FK migration applied by the dial (2026_09_14_299999).
        $applied = DB::table('migrations')->pluck('migration')->map(
            static fn (string $row): bool => str_contains($row, 'create_tf_foreign_keys'),
        );
        self::assertContains(true, $applied, 'the Task-8 FK migration must be applied by the full dial');
    }

    public function test_pk_and_peer_shapes_match_curated_dial(): void
    {
        $cons = DB::select(
            'SELECT c.conrelid::regclass::text AS tbl, c.contype, pg_get_constraintdef(c.oid) AS def '
            .'FROM pg_constraint c '
            .'JOIN pg_namespace n ON n.oid = c.connamespace '
            .'WHERE n.nspname = ? AND c.contype = ? AND c.conrelid::regclass::text LIKE ? '
            .'ORDER BY tbl',
            [self::$pgSchema, 'p', 'tf\_%'],
        );

        $pkByTable = [];
        foreach ($cons as $row) {
            $pkByTable[$row->tbl][] = $row->def;
        }

        $assertPkCovers = static function (string $table, array $columns) use ($pkByTable): void {
            $def = implode(', ', $pkByTable[$table] ?? []);
            self::assertNotSame('', $def, "{$table} has a PK");
            foreach ($columns as $column) {
                self::assertStringContainsString($column, $def, "{$table} PK covers {$column}");
            }
        };

        // Global-id tables: composite PK (account_id, id).
        foreach (['tf_users', 'tf_chats', 'tf_channels', 'tf_documents', 'tf_photos', 'tf_web_pages', 'tf_sticker_sets', 'tf_stars_transactions', 'tf_bot_infos', 'tf_folders'] as $table) {
            $assertPkCovers($table, ['account_id', 'id']);
        }

        // tf_messages: composite content-message PK (account_id, id) — the
        // wire message id IS the row id; there is no separate message_id.
        $assertPkCovers('tf_messages', ['account_id', 'id']);
        $assertPkCovers('tf_messages_entities', ['account_id', 'id', 'position']);
        $assertPkCovers('tf_dialogs', ['account_id', 'peer_type', 'peer_id']);
        $assertPkCovers('tf_updates', ['account_id', 'seq', 'position']);
        $assertPkCovers('tf_channel_participants', ['account_id', 'channel_id', 'user_id']);

        // No legacy columns anywhere in the class schema: no tl_data, no
        // constructor_id, and zero json/jsonb data types (NF5 locked rules).
        $legacyColumns = DB::select(
            'SELECT table_name, column_name FROM information_schema.columns '
            .'WHERE table_schema = ? AND (column_name = ? OR column_name = ?)',
            [self::$pgSchema, 'tl_data', 'constructor_id'],
        );
        self::assertSame([], array_map(
            static fn (object $row): string => $row->table_name.'.'.$row->column_name,
            $legacyColumns,
        ), 'no legacy tl_data / constructor_id columns in the curated dial');

        $jsonb = DB::select(
            'SELECT table_name FROM information_schema.columns '
            .'WHERE table_schema = ? AND data_type IN (?, ?)',
            [self::$pgSchema, 'json', 'jsonb'],
        );
        self::assertSame([], array_map(static fn (object $row): string => $row->table_name, $jsonb), 'zero json/jsonb columns in the curated dial');

        // Flagship native types: peer ids are signed bigint, peer_type tinyint.
        $columns = DB::select(
            'SELECT table_name, column_name, data_type FROM information_schema.columns '
            .'WHERE table_schema = ? AND ((table_name = ? AND column_name IN (?, ?)) '
            .'OR (table_name = ? AND column_name = ?))',
            [self::$pgSchema, 'tf_messages', 'peer_id', 'peer_type', 'tf_users', 'id'],
        );
        $byColumn = [];
        foreach ($columns as $row) {
            $byColumn[$row->table_name.'.'.$row->column_name] = $row->data_type;
        }
        self::assertSame('bigint', $byColumn['tf_messages.peer_id'] ?? null, 'tf_messages.peer_id is signed bigint');
        self::assertContains($byColumn['tf_messages.peer_type'] ?? null, ['smallint', 'tinyint'], 'tf_messages.peer_type is a small peer-type column');
        self::assertSame('bigint', $byColumn['tf_users.id'] ?? null, 'tf_users.id is signed bigint');

        // Constructor discriminator lives as text/varchar, never an int.
        $constructorCols = DB::select(
            'SELECT table_name, data_type FROM information_schema.columns '
            .'WHERE table_schema = ? AND column_name = ?',
            [self::$pgSchema, 'constructor'],
        );
        $types = array_map(static fn (object $row): string => $row->data_type, $constructorCols);
        self::assertContains('text', $types, 'constructor columns exist as text/varchar, not int');
    }

    public function test_p2_nested_update_new_message_roundtrip_on_postgres(): void
    {
        $this->insertAccounts(7, 8);

        // Global-ID sidecars written straight to the curated identity surface:
        // the UpdateIngestor handles message/update facts only (user/channel
        // sidecars land via their domain tables).
        (new TfChannel([...$this->channelRow(self::FIXTURE_ACCOUNT)]))->save();
        $this->insertUser(self::FIXTURE_ACCOUNT);

        // The real ingest path: updateNewMessage tree → tf_updates + children,
        // nested message → tf_messages + from_id child.
        $ingestor = new UpdateIngestor;
        $root = $ingestor->ingest(self::updateNewMessagePayload(), self::FIXTURE_ACCOUNT);
        self::assertInstanceOf(TfUpdate::class, $root);

        // Channel + user sidecars round-trip through their models.
        $channel = TfChannel::forAccount(self::FIXTURE_ACCOUNT)->sole();
        self::assertSame('Teleframe Café', $channel->title);
        self::assertSame('channel', $channel->constructor);
        self::assertTrue((bool) $channel->megagroup);
        self::assertTrue((bool) $channel->verified);
        self::assertSame(-7779317524312221622, (int) $channel->access_hash);

        $user = TfUser::forAccount(self::FIXTURE_ACCOUNT)->sole();
        self::assertSame('user', $user->constructor);
        self::assertSame('Reza', $user->firstName()->sole()->first_name);
        self::assertSame('RezaRezaei', $user->username()->sole()->username);

        // tf_messages roundtrip: wire id is the row id, peers are the
        // canonical (peer_type, peer_id) pair.
        $message = TfMessage::forAccount(self::FIXTURE_ACCOUNT)->sole();
        self::assertSame(1186, (int) $message->id);
        self::assertSame(PeerShapeTool::PEER_CHANNEL, (int) $message->peer_type, 'tf_messages.peer_type = channel');
        self::assertSame(self::FIXTURE_CHANNEL_ID, (int) $message->peer_id, 'tf_messages.peer_id = raw channel id');
        self::assertSame('message', $message->constructor);
        self::assertTrue((bool) $message->out);
        self::assertSame('Check https://t.me/teleframe from @Reza', $message->message);
        self::assertSame(1724852400, (int) $message->date);

        $from = $message->from()->sole();
        self::assertSame(PeerShapeTool::PEER_USER, (int) $from->from_id_type, 'tf_messages_from_id.type = user');
        self::assertSame(self::FIXTURE_USER_ID, (int) $from->from_id_id, 'tf_messages_from_id.id = user id');

        // tf_updates roundtrip: (account_id, seq, position) key + constructor
        // + the extracted scalar / message children.
        $update = TfUpdate::forAccount(self::FIXTURE_ACCOUNT)->sole();
        self::assertSame('updateNewMessage', $update->constructor);
        self::assertSame(1349, (int) $update->pts()->sole()->pts);
        self::assertSame(1, (int) $update->ptsCount()->sole()->pts_count);
        $linked = $update->message()->sole();
        self::assertSame(PeerShapeTool::PEER_CHANNEL, (int) $linked->peer_type);
        self::assertSame(self::FIXTURE_CHANNEL_ID, (int) $linked->peer_id);
        self::assertSame(1186, (int) $linked->message_id, 'tf_updates_message.message_id = the native message id');
    }

    public function test_re_ingest_is_idempotent_on_postgres(): void
    {
        $this->insertAccounts(7);

        $ingestor = new UpdateIngestor;
        $first = $ingestor->ingest(self::updateNewMessagePayload(), self::FIXTURE_ACCOUNT);
        $second = $ingestor->ingest(self::updateNewMessagePayload(), self::FIXTURE_ACCOUNT);

        self::assertSame((int) $first->account_id, (int) $second->account_id);
        self::assertSame(1, TfMessage::forAccount(self::FIXTURE_ACCOUNT)->count(), 're-ingest upserts, never duplicates');
        self::assertSame(1, TfUpdate::forAccount(self::FIXTURE_ACCOUNT)->count());
        self::assertSame(1186, (int) TfMessage::forAccount(self::FIXTURE_ACCOUNT)->sole()->id);
    }

    public function test_mirror_rows_are_tenant_scoped_by_composite_key(): void
    {
        $this->insertAccounts(7, 8);

        $ingestor = new UpdateIngestor;
        $ingestor->ingest(self::updateNewMessagePayload(), 7);
        $ingestor->ingest(self::updateNewMessagePayload(), 8);

        // Same (account_id, id) shape keys messages per tenant, not globally.
        self::assertSame(2, TfMessage::acrossAccounts()->count(), 'same wire message under two tenants');
        self::assertSame(2, TfUpdate::acrossAccounts()->count());
        self::assertSame(1, TfMessage::forAccount(7)->count());
        self::assertSame(1, TfMessage::forAccount(8)->count());
    }

    /**
     * @return array{account_id:int, id:int, constructor:string, title:string, access_hash:int, date:int, megagroup:bool, verified:bool}
     */
    private function channelRow(int $accountId): array
    {
        $payload = self::channelPayload();

        return [
            'account_id' => $accountId,
            'id' => (int) $payload['id'],
            'constructor' => (string) $payload['_'],
            'title' => (string) $payload['title'],
            'access_hash' => (int) $payload['access_hash'],
            'date' => (int) $payload['date'],
            'megagroup' => (bool) ($payload['megagroup'] ?? false),
            'verified' => (bool) ($payload['verified'] ?? false),
        ];
    }

    private function insertUser(int $accountId): void
    {
        $user = new TfUser([
            'account_id' => $accountId,
            'id' => self::FIXTURE_USER_ID,
            'constructor' => 'user',
        ]);
        $user->save();
        $user->accessHash()->create([...$user->childKey(), 'access_hash' => -5988024083302710253]);
        $user->firstName()->create([...$user->childKey(), 'first_name' => 'Reza']);
        $user->lastName()->create([...$user->childKey(), 'last_name' => 'Rezaei']);
        $user->username()->create([...$user->childKey(), 'username' => 'RezaRezaei']);
    }

    private function insertAccounts(int ...$accountIds): void
    {
        foreach ($accountIds as $accountId) {
            DB::table('telegram_accounts')->updateOrInsert(['id' => $accountId], [
                'id' => $accountId,
                'label' => 'full-mirror-pg-'.$accountId,
                'type' => 'user',
                'dc_id' => 2,
            ]);
        }
    }
}
