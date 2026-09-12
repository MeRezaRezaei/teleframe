<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Pg;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerIdTool;

/**
 * Domain-schema FK truth (replaces the Night W3 deferrable-FK proof).
 *
 * The TDLib domain schema (design spec §8) carries ZERO foreign keys:
 * peer refs are canonical bigint longs (PeerIdTool), not FK columns, so
 * out-of-order writes need no DEFERRABLE machinery — any insert order
 * commits. The old bucketed deferrable-FK artifact (tl_message_message
 * → tl_message_media_*, 637-file / 3045-table per-constructor set) is gone.
 */
final class DeferredFkTest extends PgTestCase
{
    private const ACCOUNT = 7;

    private const MSG_CTOR_ID = 0x7600b9d3; // message#7600b9d3 (schema/sources truth)

    private const USER_ID = 501558149;

    private const CHANNEL_ID = 1737473577;

    protected function setUp(): void
    {
        parent::setUp();
        $this->migrateDomainSet();
    }

    public function test_peer_ref_columns_hold_canonical_longs_not_uuids(): void
    {
        $colType = DB::selectOne(
            "SELECT format_type(a.atttypid, a.atttypmod) AS t FROM pg_attribute a "
            . "WHERE a.attrelid = 'tf_messages'::regclass AND a.attname = 'peer_id'",
        );
        self::assertNotNull($colType);
        self::assertSame('bigint', $colType->t, 'tf_messages.peer_id is a canonical long column');

        DB::table('tf_messages')->insert([
            'id' => 9001,
            'message_id' => 7,
            'peer_id' => PeerIdTool::userLong(self::USER_ID),
            'from_id' => PeerIdTool::userLong(self::USER_ID),
            'date' => 1724852400,
            'constructor_id' => self::MSG_CTOR_ID,
            'account_id' => self::ACCOUNT,
            'message_text' => 'canonical peer longs',
            'tl_data' => json_encode(['_' => 'message', 'id' => 7]),
        ]);

        self::assertSame(1, DB::table('tf_messages')
            ->where('id', 9001)
            ->where('peer_id', PeerIdTool::userLong(self::USER_ID))
            ->count(), 'peer long round-trips through the truth column');
    }

    /**
     * Spec §8: fk_count 0 on the tf_* domain tables. PKs and uniques are
     * constraints, not FKs — the catalog must hold no contype='f' rows
     * whose referencing table is tf_*.
     */
    public function test_domain_tables_carry_zero_foreign_keys(): void
    {
        $fkTables = array_map(
            static fn (object $row): string => $row->tbl,
            DB::select(
                'SELECT c.conrelid::regclass::text AS tbl FROM pg_constraint c '
                . 'JOIN pg_namespace n ON n.oid = c.connamespace '
                . "WHERE n.nspname = ? AND c.contype = 'f'",
                [self::$pgSchema],
            ),
        );
        $domainFks = array_values(array_filter(
            $fkTables,
            static fn (string $tbl): bool => str_starts_with($tbl, 'tf_'),
        ));

        self::assertSame([], $domainFks, 'fk_count 0 on the tf_* domain tables');
    }

    /**
     * Out-of-order ingest inside ONE transaction: the message row lands
     * BEFORE its referenced user/channel rows in the same transaction.
     * With zero FKs there is nothing to defer — any order commits.
     */
    public function test_child_before_parent_in_one_transaction_commits(): void
    {
        DB::transaction(function (): void {
            // Message first: references a user + channel that do NOT exist yet.
            DB::table('tf_messages')->insert([
                'id' => 1001,
                'message_id' => 42,
                'peer_id' => PeerIdTool::channelLong(self::CHANNEL_ID),
                'from_id' => PeerIdTool::userLong(self::USER_ID),
                'date' => 1724852400,
                'constructor_id' => self::MSG_CTOR_ID,
                'account_id' => self::ACCOUNT,
                'message_text' => 'out-of-order child',
                'tl_data' => json_encode(['_' => 'message', 'id' => 42]),
            ]);

            // Parents afterwards, SAME transaction — no deferred FK to satisfy.
            DB::table('tf_users')->insert([
                'id' => self::USER_ID,
                'constructor_id' => 0x2abae24,
                'account_id' => self::ACCOUNT,
                'first_name' => 'Reza',
                'tl_data' => json_encode(['_' => 'user', 'id' => self::USER_ID]),
            ]);
            DB::table('tf_channels')->insert([
                'id' => self::CHANNEL_ID,
                'constructor_id' => 0xc911c155,
                'account_id' => self::ACCOUNT,
                'title' => 'Teleframe Café',
                'tl_data' => json_encode(['_' => 'channel', 'id' => self::CHANNEL_ID]),
            ]);
        });

        self::assertSame(1, DB::table('tf_messages')->where('id', 1001)->count(), 'message row committed');
        self::assertSame(1, DB::table('tf_users')->where('id', self::USER_ID)->where('account_id', self::ACCOUNT)->count());
        self::assertSame(1, DB::table('tf_channels')->where('id', self::CHANNEL_ID)->where('account_id', self::ACCOUNT)->count());
    }

    /**
     * Update path: tf_updates accepts a row with extracted pts columns
     * plus full JSONB tl_data, with no parent row required.
     */
    public function test_update_path_writes_tf_updates_row(): void
    {
        DB::table('tf_updates')->insert([
            'id' => 5001,
            'constructor_id' => 0x1f2b0afd, // updateNewMessage
            'account_id' => self::ACCOUNT,
            'pts' => 1349,
            'pts_count' => 1,
            'tl_data' => json_encode(['_' => 'updateNewMessage', 'pts' => 1349, 'pts_count' => 1]),
        ]);

        $row = DB::table('tf_updates')->where('id', 5001)->first();
        self::assertNotNull($row);
        self::assertSame(1349, (int) $row->pts);
        $tlData = is_array($row->tl_data) ? $row->tl_data : json_decode((string) $row->tl_data, true);
        self::assertSame('updateNewMessage', (string) ($tlData['_'] ?? ''));
    }
}
