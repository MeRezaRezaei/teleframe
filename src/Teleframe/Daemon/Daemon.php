<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Daemon;

use Closure;
use InvalidArgumentException;
use Throwable;

/**
 * Sequential multi-account supervisor (plan Phase 3, Task 4), ported from
 * the owner's fork daemon design onto sync PHP — no event loop, no redis:
 * workers are built by the injected $workerFactory (bus/sink wiring lives
 * in the host bootstrap, docs/bus.md) and supervised round-robin:
 *
 *  - every account gets a TICK_SECONDS time slice per rotation; the
 *    worker's stop hook fires on slice end, so one wedged account can
 *    never starve the others;
 *  - a thrown worker is isolated for a backoff ladder rung (2s, 5s, 15s,
 *    60s cap) while the rotation moves on; a healthy end of shift resets
 *    the account's failure count (backoff restart);
 *  - SIGTERM/SIGINT (pcntl, guarded) or stop() end the loop gracefully:
 *    clean stop → exit 0;
 *  - all-fail give-up: once EVERY account has failed its way up the whole
 *    ladder (>= 4 consecutive failures each — no host app fixable state
 *    in-process), run() returns 1 and the init system (systemd
 *    Restart=on-failure, docs/bus.md) owns the revival.
 *
 * While every account is cooling down the supervisor sleeps to the
 * earliest cooldown expiry (interruptibly) instead of busy-looping.
 *
 * The seated account set is MUTABLE (Cycle-5 item 1): seat()/unseat()
 * add or remove accounts while run() is going, so a host registering or
 * deleting tf_accounts rows can hot re-seat workers with zero restart.
 * seats are keyed by account_id; seat() on an already-seated account is
 * a no-op (the running worker keeps its connection — no reconnect).
 * Boot-time accountsConfig stays the bootstrap set; an optional onLoop()
 * handler runs at the top of every rotation so the host can re-read its
 * live source (the tf_accounts registry) and diff-seat from it.
 */
final class Daemon
{
    /** Healthy tick length per account (seconds) before the rotation moves on. */
    public const TICK_SECONDS = 30;

    /** Consecutive-failure backoff ladder (isolation seconds), capped at the last rung. */
    public const BACKOFF_LADDER = [2, 5, 15, 60];

    private bool $shouldStop = false;

    private readonly Closure $workerFactory;

    private readonly Closure $sleep;

    private readonly Closure $now;

    /** @var Closure(): void|null rotation-top handler (see reseat in DaemonCommand) */
    private ?Closure $onLoop = null;

    /**
     * The live seated set, keyed by account_id: seeded by bootAccounts()
     * from the constructor accountsConfig, then mutated by seat()/unseat().
     *
     * @var array<int, array{worker: WorkerInterface, failures: int, isolated_until: int}>
     */
    private array $accounts = [];

    /** @var array<int, string> account_id => message of its last failure */
    private array $lastErrors = [];

    /**
     * @param  list<array<string, mixed>>  $accountsConfig  per-account {account_id, ...} shapes
     *                                                      (same configs AccountWorker consumes)
     * @param  callable(array<string, mixed>): WorkerInterface  $workerFactory  builds the
     *                                                                          supervised worker for one account config
     * @param  (callable(int): int)|null  $sleep  injectable sleeper (tests)
     * @param  (callable(): int)|null  $now  injectable clock (tests)
     */
    public function __construct(
        private readonly array $accountsConfig,
        callable $workerFactory,
        ?callable $sleep = null,
        ?callable $now = null,
    ) {
        $this->workerFactory = Closure::fromCallable($workerFactory);
        $this->sleep = $sleep !== null ? Closure::fromCallable($sleep) : Closure::fromCallable('sleep');
        $this->now = $now !== null ? Closure::fromCallable($now) : Closure::fromCallable('time');
    }

    /**
     * Request a graceful stop (the pcntl signal path and tests land here).
     */
    public function stop(): void
    {
        $this->shouldStop = true;
    }

    /**
     * Register a rotation-top handler: invoked once per main-loop iteration
     * BEFORE any account ticks, so a host can re-read its live account
     * source and seat()/unseat() diff-styled. Idempotent — a second call
     * replaces the previous handler.
     *
     * @param  callable(): void  $onLoop
     */
    public function onLoop(callable $onLoop): void
    {
        $this->onLoop = Closure::fromCallable($onLoop);
    }

    /**
     * Seat a worker for one account at runtime (hot re-seat, no restart).
     * The worker is built lazily by the injected $workerFactory; an already
     * seated account is LEFT RUNNING (no reconnect).
     *
     * @param  array<string, mixed>  $config  per-account {account_id, ...} shape
     */
    public function seat(array $config): void
    {
        $accountId = (int) ($config['account_id'] ?? 0);

        if ($accountId <= 0) {
            throw new InvalidArgumentException(
                'every daemon account config requires a positive int account_id',
            );
        }

        if (isset($this->accounts[$accountId])) {
            return; // keep the seated worker's connection — no reconnect
        }

        $this->accounts[$accountId] = [
            'worker' => ($this->workerFactory)($config),
            'failures' => 0,
            'isolated_until' => 0,
        ];
    }

    /**
     * Stop supervising one account at runtime (hot re-seat, no restart):
     * the account leaves the rotation and its last-error state is cleared.
     */
    public function unseat(int $accountId): void
    {
        unset($this->accounts[$accountId], $this->lastErrors[$accountId]);
    }

    /**
     * @return list<int> currently seated account ids (seating order)
     */
    public function seatedIds(): array
    {
        return array_keys($this->accounts);
    }

    /**
     * @return array<int, string> account_id => message of the last failure (live view)
     */
    public function lastErrors(): array
    {
        return $this->lastErrors;
    }

    /**
     * Supervise until a graceful stop (exit 0) or the all-fail give-up (exit 1).
     */
    public function run(): int
    {
        $this->installSignalHandlers();

        $this->bootAccounts();

        if ($this->accounts === []) {
            return 0; // nothing configured to supervise — a clean exit, not a failure
        }

        while (! $this->shouldStop) {
            if ($this->onLoop !== null) {
                ($this->onLoop)(); // host re-reads its live registry and diff-seats (hot re-seat)
            }

            $tickable = 0;

            // Iterate a snapshot of ids: onLoop()/seat()/unseat() mutations
            // land between rotations, never mid-rotation.
            foreach (array_keys($this->accounts) as $accountId) {
                if ($this->shouldStop) {
                    break;
                }

                $account = &$this->accounts[$accountId];

                if (($this->now)() < $account['isolated_until']) {
                    continue; // cooling down after failures — others continue
                }

                $tickable++;

                try {
                    $account['worker']->run($this->tickStop(($this->now)() + self::TICK_SECONDS));
                    $account['failures'] = 0; // healthy end of shift
                    unset($this->lastErrors[$accountId]);
                } catch (Throwable $e) {
                    $account['failures']++;
                    $account['isolated_until'] = ($this->now)()
                        + self::BACKOFF_LADDER[min($account['failures'] - 1, count(self::BACKOFF_LADDER) - 1)];
                    $this->lastErrors[$accountId] = $e->getMessage();
                }
            }
            unset($account);

            if ($this->allFailed($this->accounts)) {
                return 1; // every account climbed the whole ladder — let the init system revive us
            }

            if ($tickable === 0 && ! $this->shouldStop) {
                $this->sleepToEarliestCooldown($this->accounts);
            }
        }

        return 0;
    }

    /**
     * Seed the seated set from the constructor bootstrap configs.
     */
    private function bootAccounts(): void
    {
        $this->accounts = [];

        foreach ($this->accountsConfig as $config) {
            $accountId = (int) ($config['account_id'] ?? 0);

            if ($accountId <= 0) {
                throw new InvalidArgumentException(
                    'every daemon account config requires a positive int account_id',
                );
            }

            $this->accounts[$accountId] = [
                'worker' => ($this->workerFactory)($config),
                'failures' => 0,
                'isolated_until' => 0,
            ];
        }
    }

    /**
     * The stop hook handed to a worker for its tick: the daemon-wide stop
     * OR the end of this account's time slice.
     *
     * @return Closure(): bool
     */
    private function tickStop(int $deadline): Closure
    {
        return function () use ($deadline): bool {
            return $this->shouldStop || ($this->now)() >= $deadline;
        };
    }

    /**
     * All-fail give-up predicate: true ONLY when the set is non-empty AND
     * every account has climbed the whole backoff ladder (failures >= the
     * number of BACKOFF_LADDER rungs).
     *
     * EMPTY INPUT ⇒ FALSE, deliberately: run() already treats a zero-account
     * roster as a clean no-op (returns 0), so an empty set reaching here must
     * never flip into the exit-1 give-up path (a no-seat rotation is not a
     * failure). Defined + pinned by DaemonAllFailedW42Test.
     *
     * @param  array<int, array{worker: WorkerInterface, failures: int, isolated_until: int}>  $accounts
     */
    private function allFailed(array $accounts): bool
    {
        if ($accounts === []) {
            return false;
        }

        $rungs = count(self::BACKOFF_LADDER);

        foreach ($accounts as $account) {
            if ($account['failures'] < $rungs) {
                return false;
            }
        }

        return true;
    }

    /**
     * Nothing is tickable (every account cooling): sleep to the earliest
     * cooldown expiry in stop-responsive 1s chunks.
     *
     * @param  array<int, array{worker: WorkerInterface, failures: int, isolated_until: int}>  $accounts
     */
    private function sleepToEarliestCooldown(array $accounts): void
    {
        $earliest = PHP_INT_MAX;

        foreach ($accounts as $account) {
            $earliest = min($earliest, $account['isolated_until']);
        }

        $wait = max(1, $earliest - ($this->now)());

        for ($slept = 0; $slept < $wait && ! $this->shouldStop; $slept++) {
            ($this->sleep)(1);
        }
    }

    /**
     * Best-effort pcntl wiring (mirrors IngestCommand): SIGTERM/SIGINT
     * flip the daemon-wide stop flag. @return bool true when handlers were armed.
     */
    private function installSignalHandlers(): bool
    {
        if (! function_exists('pcntl_signal') || ! function_exists('pcntl_async_signals')) {
            return false;
        }

        pcntl_async_signals(true);
        $halt = function (): void {
            $this->stop();
        };
        pcntl_signal(SIGTERM, $halt);
        pcntl_signal(SIGINT, $halt);

        return true;
    }
}
