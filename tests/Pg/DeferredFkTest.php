<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Pg;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Uid\UuidV7;

/**
 * Night W3 deferrable-FK proof — the behavior sqlite structurally cannot
 * show (it ignores DEFERRABLE and never runs the bucketed cross-type FK
 * file): cross-type ref FKs are DEFERRABLE INITIALLY DEFERRED, so a child
 * row may land BEFORE its referenced parent inside one transaction, and a
 * real violation surfaces at COMMIT — not at INSERT.
 */
final class DeferredFkTest extends PgTestCase
{
    private const ACCOUNT = 7;

    private const MSG_CTOR_ID = 0x7600b9d3; // message#7600b9d3 (schema/sources truth)

    private const PEER_USER_CTOR_ID = 0x59511722; // peerUser#59511722

    protected function setUp(): void
    {
        parent::setUp();
        // Full generated set — the bucketed deferrable-FK artifact spans all
        // 3678 tables and is NOT part of the shipped dial, so the ingest
        // subset alone can never prove deferrable behavior.
        $this->migrateFullGeneratedSet();
    }

    /**
     * The bucketed artifact's own truth: catalog says DEFERRABLE + INITIALLY
     * DEFERRED for the message→peer cross-type FKs.
     */
    public function test_cross_type_fk_is_deferrable_initially_deferred(): void
    {
        $row = DB::selectOne(
            'SELECT conname, condeferrable, condeferred FROM pg_constraint '
            . "WHERE conrelid = 'tl_message_message'::regclass AND conname IN (?, ?)",
            ['tl_message_message_from_id_foreign', 'tl_message_message_peer_id_foreign'],
        );

        self::assertNotNull($row, 'tl_message_message_from_id_foreign / peer_id constraint exists');
        self::assertTrue((bool) $row->condeferrable, 'cross-type FK is DEFERRABLE');
        self::assertTrue((bool) $row->condeferred, 'cross-type FK is INITIALLY DEFERRED');
    }

    /**
     * Out-of-order ingest inside ONE transaction: the message instance
     * lands with from_id pointing at a peer that is inserted AFTER it in
     * the same transaction — impossible with an IMMEDIATE constraint,
     * routine with INITIALLY DEFERRED.
     */
    public function test_child_before_parent_in_one_transaction_commits(): void
    {
        $messageId = (string) UuidV7::v7();
        $peerId = (string) UuidV7::v7();

        DB::beginTransaction();
        try {
            // Child first: anchor + instance referencing a peer that does
            // NOT exist yet (deferred FK — INSERT succeeds).
            DB::table('tl_message')->insert([
                'id' => $messageId,
                'constructor_id' => self::MSG_CTOR_ID,
                'constructor_name' => 'message',
                'account_id' => self::ACCOUNT,
            ]);
            DB::table('tl_message_message')->insert([
                'id' => $messageId,
                'tl_id' => 42,
                'from_id' => $peerId, // dangling on purpose
                'peer_id' => $peerId, // dangling on purpose
                'date' => 1724852400,
                'message' => 'out-of-order child',
            ]);

            // Parent afterwards, SAME transaction — the deferred FK
            // checks at COMMIT now find it.
            DB::table('tl_peer')->insert([
                'id' => $peerId,
                'constructor_id' => self::PEER_USER_CTOR_ID, // peerUser
                'constructor_name' => 'peerUser',
                'account_id' => self::ACCOUNT,
            ]);
            DB::table('tl_peer_peer_user')->insert([
                'id' => $peerId,
                'user_id' => 501558149,
            ]);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            self::fail('deferred out-of-order transaction must commit, got: ' . $e->getMessage());
        }

        self::assertSame(1, DB::table('tl_message_message')->where('id', $messageId)->where('from_id', $peerId)->count(), 'committed child row references the late parent');
        self::assertSame(1, DB::table('tl_peer_peer_user')->where('id', $peerId)->count());
    }

    /**
     * Violation case: a genuinely dangling from_id INSERTS fine (deferred),
     * and the failure surfaces at COMMIT with SQLSTATE 23503.
     */
    public function test_real_violation_fails_at_commit_not_at_insert(): void
    {
        $messageId = (string) UuidV7::v7();

        DB::beginTransaction();
        // The INSERT itself must NOT throw — that is the deferrable part.
        DB::table('tl_message')->insert([
            'id' => $messageId,
            'constructor_id' => self::MSG_CTOR_ID,
            'constructor_name' => 'message',
            'account_id' => self::ACCOUNT,
        ]);
        DB::table('tl_message_message')->insert([
            'id' => $messageId,
            'tl_id' => 43,
            'from_id' => (string) UuidV7::v7(), // no tl_peer row will ever exist
            'peer_id' => (string) UuidV7::v7(), // no tl_peer row will ever exist
            'date' => 1724852400,
            'message' => 'doomed at commit',
        ]);
        // ... no parent arrives: COMMIT is where the deferred check runs.

        try {
            DB::commit();
            self::fail('committing a dangling deferred FK must fail');
        } catch (QueryException $e) {
            self::assertSame('23503', (string) $e->getCode(), 'foreign_key_violation raised by COMMIT');
            self::assertStringContainsString('tl_message_message_from_id_foreign', $e->getMessage());
        } catch (\PDOException $e) {
            // Laravel's commit() path rethrows the driver-level PDOException
            // without the QueryException wrapper — same proof, same codes.
            self::assertSame('23503', (string) $e->getCode(), 'foreign_key_violation raised by COMMIT');
            self::assertStringContainsString('tl_message_message_from_id_foreign', $e->getMessage());
        }

        // The aborted transaction left nothing behind.
        self::assertSame(0, DB::table('tl_message_message')->where('id', $messageId)->count());
    }

    /**
     * Control for the same proof: the instance→anchor FK created inline by
     * Schema (foreignUuid…constrained) is the standard IMMEDIATE kind —
     * deferral is a property the generator attaches to CROSS-type refs only.
     */
    public function test_inline_instance_fk_stays_immediate(): void
    {
        $row = DB::selectOne(
            'SELECT condeferrable, condeferred FROM pg_constraint '
            . "WHERE conrelid = 'tl_message_message'::regclass AND conname = ?",
            ['tl_message_message_id_foreign'],
        );

        self::assertNotNull($row, 'inline instance→anchor FK exists');
        self::assertFalse((bool) $row->condeferrable, 'inline FK is NOT deferrable');
        self::assertFalse((bool) $row->condeferred);
    }
}
