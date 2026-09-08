<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Backfill;

use MeRezaRezaei\Teleframe\Backfill\FetchQueue;
use PHPUnit\Framework\TestCase;

/**
 * Plan P3 Task 5: fetch queue port (peer list, attempt accounting,
 * dead-lettering, quota-slice headroom math) onto plain PHP arrays —
 * the fork's SQL-backed semantics without SQL.
 */
final class FetchQueueTest extends TestCase
{
    public function test_quota_slice_mirrors_fork_headroom_math(): void
    {
        // intdiv(intdiv(remaining, 2), cost) — >= 50% always reserved.
        self::assertSame(5, FetchQueue::quotaSlice(10, 1));
        self::assertSame(5, FetchQueue::quotaSlice(100, 10));
        self::assertSame(0, FetchQueue::quotaSlice(1, 1), 'odd remainder reserves everything');
        self::assertSame(0, FetchQueue::quotaSlice(0, 1));
        self::assertSame(0, FetchQueue::quotaSlice(-5, 1));
        self::assertSame(0, FetchQueue::quotaSlice(10, 0), 'zero-cost fetch is refused, not infinite');
    }

    public function test_push_pop_is_fifo(): void
    {
        $queue = new FetchQueue();
        $queue->push('@a');
        $queue->push('@b');

        self::assertSame('@a', $queue->pop());
        self::assertSame('@b', $queue->pop());
        self::assertNull($queue->pop());
        self::assertSame(['pending' => 0, 'dead' => 0], $queue->counts());
    }

    public function test_push_is_idempotent_while_queued(): void
    {
        $queue = new FetchQueue();
        $queue->push('@a');
        $queue->push('@a');

        self::assertSame('@a', $queue->pop());
        self::assertNull($queue->pop());
    }

    public function test_requeue_restores_a_claimed_peer_without_attempt_accounting(): void
    {
        $queue = new FetchQueue();
        $queue->push('@a');
        $queue->push('@b');
        $queue->fail('@a', 'earlier failure'); // attempts: 1

        self::assertSame('@a', $queue->pop());
        $queue->requeue('@a'); // deferred, not failed

        self::assertSame('@a', $queue->pop(), 'requeued peer keeps its front position');
        self::assertSame('@b', $queue->pop());
        self::assertSame(1, $queue->attemptsFor('@a'), 'requeue neither counts nor clears attempts');
        $queue->requeue('@a');
        $queue->requeue('@a');
        self::assertSame(1, $queue->counts()['pending'], 'requeue is idempotent');
    }

    public function test_fail_requeues_until_five_attempts_then_dead_letters(): void
    {
        $queue = new FetchQueue();
        $queue->push('@doomed');

        for ($i = 1; $i <= 4; $i++) {
            $peer = $queue->pop();
            self::assertSame('@doomed', $peer);
            $queue->fail($peer, 'rpc.DatabaseError'); // back to pending, not dead yet
        }

        self::assertSame('@doomed', $queue->pop());
        $queue->fail('@doomed', 'rpc timeout');

        self::assertNull($queue->pop(), 'dead peer never re-queues');
        self::assertSame(1, $queue->deadLetterCount());
        self::assertSame(['@doomed'], $queue->deadLetters());
        self::assertSame(['@doomed' => 'rpc timeout'], $queue->deadReasons());
        self::assertSame(['pending' => 0, 'dead' => 1], $queue->counts());
        self::assertSame(5, $queue->attemptsFor('@doomed'), 'attempts stay readable after death');
    }

    public function test_push_after_death_is_ignored(): void
    {
        $queue = new FetchQueue();
        $queue->push('@a');
        $queue->pop();

        for ($i = 0; $i < FetchQueue::MAX_ATTEMPTS; $i++) {
            $queue->fail('@a', 'gone');
        }

        $queue->push('@a');

        self::assertNull($queue->pop(), 'no zombie revival from the dead-letter list');
        self::assertSame(['pending' => 0, 'dead' => 1], $queue->counts());
    }

    public function test_failed_peer_goes_to_back_of_queue(): void
    {
        $queue = new FetchQueue();
        $queue->push('@front');
        $queue->push('@back');

        $front = $queue->pop();
        self::assertSame('@front', $front);
        $queue->fail($front, 'transient');

        self::assertSame('@back', $queue->pop(), 'healthy peer advances while the failed one waits');
        self::assertSame('@front', $queue->pop(), 'failed peer re-queued behind it');
    }
}
