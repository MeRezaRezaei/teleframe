<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Daemon;

use MeRezaRezaei\Teleframe\Core\Contracts\UpdateSinkInterface;
use MeRezaRezaei\Teleframe\Daemon\AccountWorker;
use MeRezaRezaei\Teleframe\Tests\Support\FakeUserScope;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * AccountWorker supervision tests (plan Phase 3, Task 4) — fully offline:
 * the scope factory seam returns FakeUserScope scripts (rebuilt scopes
 * RESUME the script where the dead one stopped), the sleeper is recorded,
 * no pcntl/redis/network anywhere.
 */
final class AccountWorkerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Plain-PHP context (the worker's standalone environment): tests
        // that ran earlier in this process can leave a torn-down Laravel
        // app on the Event facade — teleframe's gap/resync dispatch would
        // then throw BindingResolutionException mid-poll. Pin "no app".
        \Illuminate\Support\Facades\Facade::setFacadeApplication(null);
    }

    public function test_config_requires_a_positive_account_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('account_id');

        new AccountWorker(['session_string' => 'x']);
    }

    public function test_account_id_accessor(): void
    {
        self::assertSame(42, (new AccountWorker(['account_id' => 42]))->accountId());
    }

    /**
     * The brief's canned shape: getDifference answers differenceEmpty
     * once, then the shouldStop flip ends the loop through the stop-aware
     * scope — clean return, cursor captured for persistence.
     */
    public function test_polls_until_should_stop_then_reports_the_cursor(): void
    {
        $sink = new RecordingSink();
        $factory = self::factory([
            FakeUserScope::emptyDifference(101, 1_700_000_100),
            FakeUserScope::emptyDifference(102, 1_700_000_101), // never fetched: pre-flight stops first
        ]);

        $checks = 0;
        $shouldStop = static function () use (&$checks): bool {
            return ++$checks >= 3; // loop-top, pre-flight, then the stop lands
        };

        $worker = new AccountWorker(['account_id' => 42], $factory->factory, ['pts' => 100, 'date' => 1, 'qts' => 0, 'seq' => 0]);
        $worker->run($sink, $shouldStop);

        self::assertCount(1, $factory->scopes);
        self::assertCount(1, $factory->scopes[0]->calls);
        self::assertSame('updates.getDifference', $factory->scopes[0]->calls[0]['method']);
        self::assertSame(100, $factory->scopes[0]->calls[0]['params']['pts'], 'the resume cursor was sent');
        self::assertSame(
            ['pts' => 101, 'date' => 1_700_000_100, 'qts' => 0, 'seq' => 0],
            $worker->lastSequenceState(),
            'differenceEmpty state adopted and reported',
        );
    }

    public function test_slices_stream_to_the_sink_until_should_stop(): void
    {
        $sink = new RecordingSink();
        $factory = self::factory([
            FakeUserScope::sliceWithUpdate(101),
            FakeUserScope::sliceWithUpdate(102),
            FakeUserScope::sliceWithUpdate(103),
            FakeUserScope::sliceWithUpdate(104),
        ]);

        $worker = new AccountWorker(['account_id' => 42], $factory->factory);
        $worker->run($sink, static fn (): bool => $sink->count >= 3);

        self::assertSame(3, $sink->count);
        self::assertSame('updateNewMessage', $sink->updates[0]['_']);
        self::assertSame(103, $worker->lastSequenceState()['pts']);
    }

    public function test_flood_wait_sleeps_interruptibly_then_resumes_with_the_cursor(): void
    {
        $sleeps = [];
        $factory = self::factory([
            FakeUserScope::flood(3),
            FakeUserScope::sliceWithUpdate(105),
            FakeUserScope::sliceWithUpdate(106),
        ]);

        $worker = new AccountWorker(
            ['account_id' => 42],
            $factory->factory,
            null,
            static function (int $seconds) use (&$sleeps): int {
                $sleeps[] = $seconds;

                return $seconds;
            },
        );

        $sink = new RecordingSink();
        $worker->run($sink, static fn (): bool => $sink->count >= 2);

        self::assertSame([1, 1, 1], $sleeps, 'FloodWait 3s backs off in three stop-responsive chunks');
        self::assertSame(2, $sink->count, 'polling resumed after the flood window');
        self::assertSame(2, $factory->calls, 'the scope was rebuilt once for the retry attempt');
        self::assertSame(106, $worker->lastSequenceState()['pts']);
    }

    public function test_flood_sleep_is_aborted_by_should_stop(): void
    {
        $sleeps = [];
        $factory = self::factory([FakeUserScope::flood(300)]);

        $checks = 0;
        $shouldStop = static function () use (&$checks): bool {
            return ++$checks > 3; // loop-top, pre-flight, then one chunk into the flood sleep
        };

        $worker = new AccountWorker(
            ['account_id' => 42],
            $factory->factory,
            null,
            static function (int $seconds) use (&$sleeps): int {
                $sleeps[] = $seconds;

                return $seconds;
            },
        );

        $worker->run(new RecordingSink(), $shouldStop);

        self::assertCount(1, $sleeps, 'a 300s flood backs off in chunks until the stop lands');
    }

    public function test_dc_migration_rebuilds_the_scope_at_the_new_dc(): void
    {
        $factory = self::factory([
            FakeUserScope::dcMigration(4),
            FakeUserScope::sliceWithUpdate(110),
        ]);

        $worker = new AccountWorker(['account_id' => 42, 'dc' => 2], $factory->factory);
        $sink = new RecordingSink();
        $worker->run($sink, static fn (): bool => $sink->count >= 1);

        self::assertSame([['account_id' => 42, 'dc' => 2], ['account_id' => 42, 'dc' => 4]], $factory->args);
        self::assertSame(1, $sink->count, 'polling resumed at DC 4');
        self::assertSame(110, $worker->lastSequenceState()['pts']);
    }

    public function test_unexpected_errors_rethrow_after_three_attempts_without_progress(): void
    {
        $factory = self::factory([
            new RuntimeException('kaboom 1'),
            new RuntimeException('kaboom 2'),
            new RuntimeException('kaboom 3'),
        ]);

        $worker = new AccountWorker(['account_id' => 42], $factory->factory);

        try {
            $worker->run(new RecordingSink());
            self::fail('expected the 3rd consecutive unexpected failure to escape');
        } catch (RuntimeException $e) {
            self::assertSame('kaboom 3', $e->getMessage());
        }

        self::assertSame(3, $factory->calls);
    }

    public function test_progress_between_failures_resets_the_streak(): void
    {
        $factory = self::factory([
            new RuntimeException('kaboom 1'),
            FakeUserScope::sliceWithUpdate(120), // progress: cursor advanced — next failure is NOT consecutive
            new RuntimeException('kaboom 2'),
            new RuntimeException('kaboom 3'),
            new RuntimeException('kaboom 4'),
        ]);

        $worker = new AccountWorker(['account_id' => 42], $factory->factory);
        $sink = new RecordingSink();

        try {
            $worker->run($sink);
            self::fail('expected the streak to climb to three after the progress reset');
        } catch (RuntimeException $e) {
            self::assertSame('kaboom 4', $e->getMessage(), 'kaboom 2/3 survived the reset; only the 3rd after it escaped');
        }

        self::assertSame(1, $sink->count);
        self::assertSame(4, $factory->calls, '4 scope builds: fail, progress, fail, fail');
    }

    /**
     * Script-splicing scope factory: every scope the worker builds resumes
     * the shared step list where the previous scope stopped (one
     * getDifference per step), so supervision rebuilds replay nothing.
     *
     * @param list<array<string, mixed>|\Throwable> $steps
     * @return object{factory: \Closure, scopes: list<FakeUserScope>, calls: int, args: list<array<string, mixed>>}
     */
    private static function factory(array $steps): object
    {
        $state = new \stdClass();
        $state->scopes = [];
        $state->calls = 0;
        $state->args = [];

        $state->factory = static function (array $config, ?int $dc) use ($steps, $state): FakeUserScope {
            $consumed = 0;

            foreach ($state->scopes as $previous) {
                foreach ($previous->calls as $call) {
                    if ($call['method'] === 'updates.getDifference') {
                        $consumed++;
                    }
                }
            }

            $state->calls++;
            $state->args[] = ['account_id' => $config['account_id'], 'dc' => $dc];
            $scope = new FakeUserScope(array_slice($steps, $consumed));
            $state->scopes[] = $scope;

            return $scope;
        };

        return $state;
    }
}

final class RecordingSink implements UpdateSinkInterface
{
    /** @var list<array<string, mixed>> */
    public array $updates = [];

    public int $count = 0;

    public function handle(array $update, ?string $source = null): bool
    {
        $this->updates[] = $update;
        $this->count++;

        return true;
    }
}
