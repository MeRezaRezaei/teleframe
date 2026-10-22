<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Backfill;

use MeRezaRezaei\Teleframe\Backfill\CursorStoreContract;
use MeRezaRezaei\Teleframe\Backfill\FetchQueue;
use MeRezaRezaei\Teleframe\Backfill\MemoryCursorStore;
use PHPUnit\Framework\TestCase;

/**
 * CursorStoreContract + MemoryCursorStore (Cycle 17, verbatim 2026-09-16
 * "i need the full acount hsitory in my database to let me work with it").
 *
 * The two properties under test are the ones the whole durability story rests
 * on: a cursor NEVER rewinds, and a terminal peer STAYS terminal. If either
 * can regress, the crawl silently restarts and the full history is unreachable
 * again — which is the bug this pair exists to close.
 */
final class CursorStoreTest extends TestCase
{
    private MemoryCursorStore $store;

    protected function setUp(): void
    {
        $this->store = new MemoryCursorStore(now: static fn (): int => 1_700_000_000);
    }

    public function test_a_fresh_account_reads_as_empty_not_null(): void
    {
        self::assertSame([], $this->store->cursorsFor(7), 'an unwritten account has no cursor rows at all');
        self::assertNull($this->store->findCursor(7, 'chat'), 'an unwritten account has no cursor for the peer');
        self::assertSame([], $this->store->terminalPeers(7), 'an unwritten account has no terminal peers');
    }

    public function test_upsert_then_read_back_keeps_column_names(): void
    {
        $this->store->upsertCursor(7, [
            'peer_key' => 'chat',
            'offset_id' => 100,
            'attempts' => 2,
            'status' => CursorStoreContract::STATUS_ACTIVE,
            'reason' => '',
        ]);

        $row = $this->store->findCursor(7, 'chat');
        self::assertNotNull($row);
        self::assertSame(100, $row['offset_id']);
        self::assertSame(2, $row['attempts']);
        self::assertSame(1_700_000_000, $row['last_offset_at_unix']);
    }

    public function test_a_cursor_never_rewinds_to_a_shallower_offset(): void
    {
        $this->store->upsertCursor(7, ['peer_key' => 'chat', 'offset_id' => 100]);
        // A re-run that forgets to report progress must not rewind.
        $this->store->upsertCursor(7, ['peer_key' => 'chat', 'offset_id' => 0]);

        self::assertSame(100, (int) $this->store->findCursor(7, 'chat')['offset_id']);
    }

    public function test_a_deeper_offset_advances_the_desc_cursor(): void
    {
        // History walks DESC, so deeper = smaller id. The cursor must move
        // DOWN to 90, not stay pinned at 100.
        $this->store->upsertCursor(7, ['peer_key' => 'chat', 'offset_id' => 100]);
        $this->store->upsertCursor(7, ['peer_key' => 'chat', 'offset_id' => 90]);

        self::assertSame(90, (int) $this->store->findCursor(7, 'chat')['offset_id']);
    }

    public function test_a_first_real_offset_beats_the_untouched_zero(): void
    {
        // 0 means 'nothing crawled yet'; the first real page must win in both
        // directions, so the min() guard can never latch on an untouched 0.
        $this->store->upsertCursor(7, ['peer_key' => 'chat', 'offset_id' => 100]);
        self::assertSame(100, (int) $this->store->findCursor(7, 'chat')['offset_id']);
    }

    public function test_a_row_without_identity_is_ignored(): void
    {
        $this->store->upsertCursor(7, ['peer_key' => '   ', 'offset_id' => 100]);

        self::assertSame([], $this->store->cursorsFor(7));
    }

    public function test_terminal_peers_are_reported_for_both_finish_states(): void
    {
        $this->store->upsertCursor(7, ['peer_key' => 'done-peer', 'offset_id' => 5, 'status' => CursorStoreContract::STATUS_COMPLETE]);
        $this->store->upsertCursor(7, ['peer_key' => 'dead-peer', 'offset_id' => 3, 'status' => CursorStoreContract::STATUS_DEAD, 'reason' => 'flood_wait']);
        $this->store->upsertCursor(7, ['peer_key' => 'live-peer', 'offset_id' => 9, 'status' => CursorStoreContract::STATUS_ACTIVE]);

        $terminal = $this->store->terminalPeers(7);
        self::assertSame(
            ['done-peer' => 'complete', 'dead-peer' => 'dead'],
            $terminal,
        );
        self::assertArrayNotHasKey('live-peer', $terminal);
    }

    public function test_forget_distinguishes_a_real_reset_from_a_no_op(): void
    {
        self::assertFalse($this->store->forget(7, 'never-seen'), 'a no-op is not a reset');
        $this->store->upsertCursor(7, ['peer_key' => 'chat', 'offset_id' => 100]);
        self::assertTrue($this->store->forget(7, 'chat'));
        self::assertNull($this->store->findCursor(7, 'chat'));
    }

    public function test_accounts_are_isolated_from_each_other(): void
    {
        $this->store->upsertCursor(7, ['peer_key' => 'chat', 'offset_id' => 100]);
        $this->store->upsertCursor(8, ['peer_key' => 'chat', 'offset_id' => 42]);

        self::assertSame(100, (int) $this->store->findCursor(7, 'chat')['offset_id']);
        self::assertSame(42, (int) $this->store->findCursor(8, 'chat')['offset_id']);
    }

    public function test_seeding_restores_a_partial_attempt_count(): void
    {
        $queue = new FetchQueue;
        $queue->push('chat');
        $queue->seed('chat', 3);

        self::assertSame(3, $queue->attemptsFor('chat'));
    }

    public function test_seeding_an_exhausted_peer_dead_letters_it_immediately(): void
    {
        $queue = new FetchQueue;
        $queue->push('chat');
        $queue->seed('chat', FetchQueue::MAX_ATTEMPTS, 'flood_wait');

        // Restoring the ledger must not hand a dead peer a fresh budget of lives.
        self::assertSame(FetchQueue::MAX_ATTEMPTS, $queue->attemptsFor('chat'));
        self::assertContains('chat', $queue->deadLetters());
        self::assertNull($queue->pop(), 'a dead peer must not be claimable');
    }

    public function test_seeding_never_invents_a_job_that_was_not_queued(): void
    {
        $queue = new FetchQueue;
        $queue->seed('never-pushed', 2);

        self::assertSame(0, $queue->attemptsFor('never-pushed'));
        self::assertNull($queue->pop());
    }
}
