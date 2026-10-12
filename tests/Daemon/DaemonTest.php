<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Daemon;

use MeRezaRezaei\Teleframe\Daemon\Daemon;
use MeRezaRezaei\Teleframe\Daemon\WorkerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Daemon supervisor tests (plan Phase 3, Task 4): fake worker factory,
 * injected clock + sleeper (the fake clock only advances through the
 * sleeper or worker bodies) — no network, no redis. The signal path runs
 * only when pcntl/posix exist, mirroring IngestCommand coverage.
 */
final class DaemonTest extends TestCase
{
    private int $clock = 1_000_000;

    /** @var list<int> */
    private array $sleeps = [];

    protected function setUp(): void
    {
        $this->clock = 1_000_000;
        $this->sleeps = [];
    }

    public function test_no_accounts_is_a_clean_noop(): void
    {
        $daemon = new Daemon([], static fn (): WorkerInterface => throw new \LogicException('never built'));

        self::assertSame(0, $daemon->run());
    }

    public function test_account_config_without_account_id_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('account_id');

        (new Daemon([['session_string' => 'x']], static fn (): WorkerInterface => throw new \LogicException('never built')))->run();
    }

    public function test_clean_stop_exits_zero(): void
    {
        $holder = new \stdClass(); // {daemon: Daemon|null} — avoids stale-reference capture chains
        $holder->daemon = null;
        $runs = new \stdClass();
        $runs->count = 0;

        $daemon = new Daemon(
            [['account_id' => 7]],
            fn (): WorkerInterface => new CallbackWorker(function () use ($holder, $runs): void {
                $runs->count++;
                $holder->daemon->stop(); // a worker asking for the daemon-wide stop mid-tick
            }),
            $this->sleepSpy(),
            fn (): int => $this->clock,
        );
        $holder->daemon = $daemon;

        self::assertSame(0, $daemon->run());
        self::assertSame(1, $runs->count);
    }

    public function test_account_failure_is_isolated_and_others_continue(): void
    {
        $holder = new \stdClass();
        $holder->daemon = null;
        $runs = new \stdClass();
        $runs->sick = 0;
        $runs->healthy = 0;

        $daemon = new Daemon(
            [
                ['account_id' => 1], // always sick: throws immediately
                ['account_id' => 2], // healthy; ends the daemon after 3 shifts
            ],
            function (array $config) use ($holder, $runs): WorkerInterface {
                return (int) $config['account_id'] === 1
                    ? new CallbackWorker(function () use ($runs): void {
                        $runs->sick++;
                        throw new \RuntimeException('account 1 dead');
                    })
                    : new CallbackWorker(function () use ($holder, $runs): void {
                        $runs->healthy++;
                        $this->clock++; // a shift takes time

                        if ($runs->healthy >= 3) {
                            $holder->daemon->stop();
                        }
                    });
            },
            $this->sleepSpy(),
            fn (): int => $this->clock,
        );
        $holder->daemon = $daemon;

        self::assertSame(0, $daemon->run(), 'healthy account kept the daemon alive → clean stop');
        self::assertSame(3, $runs->healthy, 'the healthy account kept ticking while 1 was isolated');
        self::assertSame(2, $runs->sick, 'the sick account re-ticked only after its 2s cooldown lapsed');
        self::assertSame([1 => 'account 1 dead'], $daemon->lastErrors());
    }

    public function test_backoff_ladder_isolates_and_retries_a_failing_account(): void
    {
        $runs = new \stdClass();
        $runs->count = 0;

        $daemon = new Daemon(
            [['account_id' => 9]],
            fn (): WorkerInterface => new CallbackWorker(function () use ($runs): void {
                $runs->count++;
                $this->clock++;
                throw new \RuntimeException('boom');
            }),
            $this->sleepSpy(),
            fn (): int => $this->clock,
        );

        $exit = $daemon->run();

        self::assertSame(1, $exit, 'a lone account failing its way up the whole ladder → all-fail exit 1');
        self::assertSame(count(Daemon::BACKOFF_LADDER), $runs->count, 'one tick per ladder rung (2s, 5s, 15s, 60s)');
        self::assertSame(2 + 5 + 15, array_sum($this->sleeps), 'cooldowns slept in 1s chunks: rungs 1-3 (rung 4 exits)');
        self::assertSame('boom', $daemon->lastErrors()[9] ?? null);
    }

    public function test_sigterm_stops_the_loop_gracefully_when_pcntl_exists(): void
    {
        if (! function_exists('posix_kill') || ! function_exists('pcntl_signal') || ! function_exists('pcntl_async_signals')) {
            self::markTestSkipped('pcntl/posix unavailable');
        }

        $daemon = null;

        $daemon = new Daemon(
            [['account_id' => 7]],
            fn (): WorkerInterface => new CallbackWorker(static function (): void {
                posix_kill(getmypid(), SIGTERM); // signal lands mid-tick; async dispatch flips the stop flag
            }),
            $this->sleepSpy(),
            fn (): int => 1_000_000,
        );

        self::assertSame(0, $daemon->run());
    }

    /**
     * @return callable(int): int
     */
    private function sleepSpy(): callable
    {
        return function (int $seconds): int {
            $this->sleeps[] = $seconds;
            $this->clock += $seconds; // the fake clock only advances through sleeps

            return $seconds;
        };
    }
}

final class CallbackWorker implements WorkerInterface
{
    private readonly \Closure $body;

    public function __construct(callable $body)
    {
        $this->body = $body(...);
    }

    public function run(?callable $shouldStop = null): void
    {
        ($this->body)($shouldStop);
    }
}
