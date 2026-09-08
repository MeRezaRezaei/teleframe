<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Backfill;

use MeRezaRezaei\Teleframe\Backfill\BackfillWorker;
use MeRezaRezaei\Teleframe\Tests\Support\ScriptedFetch;
use MeRezaRezaei\Teleframe\Tests\Support\SleepRecorder;
use MeRezaRezaei\Teleframe\Core\Exceptions\Rpc\FloodWaitException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Plan P3 Task 5: quota-sliced history backfill loop ported from the owner's
 * fork designs onto sync PHP + teleframe's FloodWaitException. Budget is
 * counted in requests; the fork's >= 50% headroom rule caps a run at
 * intdiv(budget, 2) requests.
 */
final class BackfillWorkerTest extends TestCase
{
    private static function sleeper(): SleepRecorder
    {
        return new SleepRecorder();
    }

    private static function flood(int $seconds): FloodWaitException
    {
        return new FloodWaitException($seconds, 'FLOOD_WAIT_' . $seconds, 4200);
    }

    public function test_full_fill_three_pages_ends_done(): void
    {
        $sleep = self::sleeper();
        $worker = new BackfillWorker(10, 3600, $sleep);

        $fetch = new ScriptedFetch([
            ScriptedFetch::page(300, 295, 290),
            ScriptedFetch::page(280, 270),
            [],
        ]);
        $batches = [];

        $result = $worker->process('@peer', $fetch, static function (array $messages) use (&$batches): void {
            $batches[] = $messages;
        });

        self::assertSame('done', $result['status']);
        self::assertNull($result['reason']);
        self::assertSame(5, $result['messages']);
        self::assertSame(3, $result['requests']);
        self::assertSame(270, $result['offset_id'], 'cursor ends on oldest fetched id');
        self::assertSame([ScriptedFetch::page(300, 295, 290), ScriptedFetch::page(280, 270)], $batches);
        // offset cursor advances through the DESC pages; limit = min(remaining requests, 100)
        self::assertSame([[0, 5], [290, 4], [270, 3]], $fetch->calls);
        self::assertSame([], $sleep->seconds);
    }

    public function test_flood_mid_way_sleeps_min_of_seconds_and_cap(): void
    {
        $sleep = self::sleeper();
        $worker = new BackfillWorker(100, 3600, $sleep);

        $fetch = new ScriptedFetch([
            ScriptedFetch::page(500, 490),
            self::flood(7200), // capped at 3600
            self::flood(30),   // below cap, slept as-is
            ScriptedFetch::page(480),
            [],
        ]);

        $result = $worker->process('@peer', $fetch, static function (): void {
        });

        self::assertSame('done', $result['status']);
        self::assertSame([3600, 30], $sleep->seconds, 'sleep is min(flood seconds, cap)');
        self::assertSame(5, $result['requests'], 'flooded attempts consume budget');
        self::assertSame(3, $result['messages']);
        // the flooded call retried the SAME offset (490), then the cursor advanced
        self::assertSame([[0, 50], [490, 49], [490, 48], [490, 47], [480, 46]], $fetch->calls);
    }

    public function test_permanent_generic_failure_dead_letters_after_five_attempts(): void
    {
        $sleep = self::sleeper();
        $worker = new BackfillWorker(100, 3600, $sleep);

        $fetch = new ScriptedFetch([
            new RuntimeException('boom'),
            new RuntimeException('boom'),
            new RuntimeException('boom'),
            new RuntimeException('boom'),
            new RuntimeException('boom'),
        ]);
        $onBatch = $this->createCallableMock($this->never());

        $result = $worker->process('@peer', $fetch, $onBatch);

        self::assertSame('dead', $result['status']);
        self::assertSame('fetch_failed', $result['reason']);
        self::assertSame(5, $result['requests']);
        self::assertSame(0, $result['messages']);
        self::assertCount(5, $fetch->calls, 'exactly five attempts, no sixth');
        self::assertSame([], $sleep->seconds, 'generic failures never sleep');
    }

    public function test_cursor_resume_starts_from_callers_last_offset(): void
    {
        $sleep = self::sleeper();
        $worker = new BackfillWorker(50, 3600, $sleep);

        $fetch = new ScriptedFetch([
            ScriptedFetch::page(880, 860),
            [],
        ]);

        $result = $worker->process('@peer', $fetch, static function (): void {
        }, 900);

        self::assertSame('done', $result['status']);
        self::assertSame(860, $result['offset_id']);
        self::assertSame(2, $result['messages']);
        self::assertSame(900, $fetch->calls[0][0], 'first fetch uses the caller-supplied cursor');
    }

    public function test_budget_stop_never_overruns_half_headroom(): void
    {
        $sleep = self::sleeper();
        $worker = new BackfillWorker(8, 3600, $sleep); // max intdiv(8, 2) = 4 requests

        // Endless history: every page returns `limit` full messages.
        $fetch = new ScriptedFetch([
            ScriptedFetch::page(90, 89, 88, 87),
            ScriptedFetch::page(86, 85, 84),
            ScriptedFetch::page(83, 82),
            ScriptedFetch::page(81),
            ScriptedFetch::page(80), // must NEVER be reached
        ]);

        $result = $worker->process('@peer', $fetch, static function (): void {
        });

        self::assertSame('budget', $result['status']);
        self::assertSame('request_budget_exhausted', $result['reason']);
        self::assertSame(4, $result['requests'], 'run stops at 50% of the budget');
        self::assertCount(4, $fetch->calls, 'no request beyond the headroom slice');
        self::assertSame(10, $result['messages'], '4 + 3 + 2 + 1');
        self::assertSame(81, $result['offset_id']);
    }

    public function test_headroom_rule_holds_back_when_budget_cannot_be_halved(): void
    {
        $sleep = self::sleeper();
        $worker = new BackfillWorker(1, 3600, $sleep); // quotaSlice(1, 1) = 0

        $fetch = new ScriptedFetch([]);

        $result = $worker->process('@peer', $fetch, static function (): void {
        });

        self::assertSame('budget', $result['status']);
        self::assertSame('request_budget_exhausted', $result['reason']);
        self::assertSame(0, $result['requests']);
        self::assertSame([], $fetch->calls, 'a budget of one fetches nothing — headroom first');
    }

    public function test_consecutive_floods_dead_letter_after_five_attempts(): void
    {
        $sleep = self::sleeper();
        $worker = new BackfillWorker(100, 60, $sleep);

        $fetch = new ScriptedFetch([
            self::flood(10),
            self::flood(10),
            self::flood(10),
            self::flood(10),
            self::flood(10),
        ]);

        $result = $worker->process('@peer', $fetch, static function (): void {
        });

        self::assertSame('dead', $result['status']);
        self::assertSame('flood_wait', $result['reason']);
        self::assertSame(5, $result['requests']);
        self::assertCount(4, $sleep->seconds, 'no sleep on the attempt that trips the limit');
    }

    public function test_flood_retries_exhaust_budget_without_overrun(): void
    {
        $sleep = self::sleeper();
        $worker = new BackfillWorker(4, 3600, $sleep); // max 2 requests

        $fetch = new ScriptedFetch([
            self::flood(5),
            self::flood(5),
            ScriptedFetch::page(1), // beyond budget — must not run
        ]);

        $result = $worker->process('@peer', $fetch, static function (): void {
        });

        self::assertSame('budget', $result['status']);
        self::assertSame(2, $result['requests']);
        self::assertCount(2, $fetch->calls);
        self::assertSame([5, 5], $sleep->seconds);
    }

    private function createCallableMock(object $expectation): callable
    {
        $mock = $this->createMock(OnBatchSpy::class);
        $mock->expects($expectation)->method('__invoke');

        return static fn (array $messages): mixed => $mock($messages);
    }
}

/** @internal onBatch spy so "never" expectations are expressible */
interface OnBatchSpy
{
    /** @param list<array<string, mixed>> $messages */
    public function __invoke(array $messages): mixed;
}
