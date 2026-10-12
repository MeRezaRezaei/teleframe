<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Supervisor;

use Closure;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * The process supervisor: keeps a fixed set of ProcessSpecs alive, restarts a
 * dead one with exponential backoff, and reports what it sees.
 *
 * INJECTED SEAMS (the reason this class is plain-PHP-constructible):
 *   - the spawner (`proc_open` lives in SystemProcessSpawner, never here);
 *   - the clock (so backoff windows are testable without sleeping);
 *   - the sleeper (so a supervision loop can be driven in a test);
 *   - a PSR-3 logger (NullLogger by default — the engine stays silent).
 *
 * Construction starts NOTHING. `adopt()`/`supervise()` start processes, and
 * only those. The whole supervisor therefore survives the standalone-smoke
 * gate, which builds it with no container booted and no process spawned.
 *
 * `tick()` is the single step: poll liveness, restart what died when its
 * backoff window has elapsed, and return the resulting rows. `supervise()`
 * is a thin loop over `tick()` + the injected sleeper — which is why a test
 * can drive a full crash/restart cycle with plain-PHP fakes in microseconds.
 */
final class Supervisor
{
    private const DEFAULT_TICK_SECONDS = 5;

    /** @var array<string, ProcessSpec> */
    private array $specs = [];

    /** @var array<string, ProcessHandle|null> */
    private array $handles = [];

    /** @var array<string, int> */
    private array $restarts = [];

    /** @var array<string, int> */
    private array $nextAttemptAt = [];

    /** @var array<string, int|null> */
    private array $lastExitCode = [];

    /** @var array<string, int> */
    private array $backoff = [];

    private bool $stopped = false;

    private readonly Closure $clock;

    private readonly Closure $sleeper;

    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly ProcessSpawnerContract $spawner,
        ?Closure $clock = null,
        ?Closure $sleeper = null,
        ?LoggerInterface $logger = null,
    ) {
        $this->clock = $clock ?? static fn (): int => time();
        $this->sleeper = $sleeper ?? static function (int $seconds): void {
            if ($seconds > 0) {
                sleep($seconds);
            }
        };
        $this->logger = $logger ?? new NullLogger;
    }

    /**
     * Record a spec WITHOUT starting it.
     *
     * `adopt()` conflates two things — knowing a process exists, and running
     * it — and anything that wants the first without the second had no way to
     * ask. `teleframe:supervise --status` is exactly that: it must report what
     * the supervisor WOULD run, and must not run it. Its previous workaround
     * was to skip registration entirely, so `report()` iterated an empty set and
     * printed `supervised: []` for every configuration; the fix that adopted the
     * specs instead started them, and the command's own test caught it
     * ("--status never starts a process").
     *
     * Both share one initialiser so the two entry points cannot drift.
     */
    public function register(ProcessSpec $spec): void
    {
        $this->specs[$spec->name] ??= $spec;
        $this->restarts[$spec->name] ??= 0;
        $this->nextAttemptAt[$spec->name] ??= 0;
        $this->lastExitCode[$spec->name] ??= null;
        $this->backoff[$spec->name] ??= 0;
    }

    /**
     * Register a process and start it. Duplicate names are adopted once
     * (a second adopt of a live name is a no-op, not a second process).
     */
    public function adopt(ProcessSpec $spec): void
    {
        $this->register($spec);

        if ($this->handles[$spec->name] ?? null) {
            return;
        }

        $this->start($spec->name);
    }

    /**
     * Adopt every spec, then supervise until stop() or $maxTicks ticks.
     *
     * @param  list<ProcessSpec>  $specs
     * @param  int|null  $maxTicks  bound the loop (null = until stopped); a test/smoke safety valve.
     * @param  Closure(): bool|null  $shouldContinue  external stop condition (the CLI's signal flag).
     * @return list<ProcessState> the final report.
     */
    public function supervise(
        array $specs,
        int $tickSeconds = self::DEFAULT_TICK_SECONDS,
        ?int $maxTicks = null,
        ?Closure $shouldContinue = null,
    ): array {
        foreach ($specs as $spec) {
            $this->adopt($spec);
        }

        $ticks = 0;
        while (! $this->stopped && ! ($shouldContinue !== null && $shouldContinue())) {
            $this->tick();

            if ($maxTicks !== null && ++$ticks >= $maxTicks) {
                break;
            }

            ($this->sleeper)($tickSeconds);
        }

        return $this->report();
    }

    /**
     * One supervision step: detect deaths, restart what is due, return rows.
     *
     * @return list<ProcessState>
     */
    public function tick(): array
    {
        $now = ($this->clock)();

        foreach ($this->specs as $name => $spec) {
            $handle = $this->handles[$name] ?? null;

            if ($handle !== null && $this->spawner->isAlive($handle)) {
                continue; // still running — nothing to do.
            }

            if ($handle !== null) {
                $this->onDeath($name);
            }

            if ($now < ($this->nextAttemptAt[$name] ?? 0)) {
                continue; // still inside its backoff window.
            }

            if ($this->isExhausted($spec)) {
                // Out of restarts: stay down and say so, rather than silently
                // spinning or quietly disappearing from the report.
                continue;
            }

            $this->start($name);
        }

        return $this->report();
    }

    /**
     * The current rows, without side effects (safe to call from an ops read).
     *
     * @return list<ProcessState>
     */
    public function report(): array
    {
        $rows = [];

        foreach ($this->specs as $name => $spec) {
            $handle = $this->handles[$name] ?? null;
            $running = $handle !== null && $this->spawner->isAlive($handle);
            $backoff = $this->backoff[$name] ?? 0;

            $rows[] = new ProcessState(
                name: $name,
                status: match (true) {
                    $running => ProcessState::RUNNING,
                    $this->stopped => ProcessState::STOPPED,
                    $this->isExhausted($spec) => ProcessState::EXHAUSTED,
                    $backoff > 0 => ProcessState::BACKOFF,
                    default => ProcessState::RESTARTING,
                },
                pid: $running ? $handle->pid : null,
                restarts: $this->restarts[$name] ?? 0,
                lastExitCode: $this->lastExitCode[$name] ?? null,
                backoffSeconds: $running ? 0 : $backoff,
                nextAttemptAt: $running ? 0 : ($this->nextAttemptAt[$name] ?? 0),
            );
        }

        return $rows;
    }

    public function state(string $name): ?ProcessState
    {
        foreach ($this->report() as $row) {
            if ($row->name === $name) {
                return $row;
            }
        }

        return null;
    }

    /** Ask the supervision loop to stop (signal handler path). */
    public function requestStop(): void
    {
        $this->stopped = true;
    }

    /** Terminate every supervised process and stop restarting. */
    public function stop(): void
    {
        $this->stopped = true;

        foreach (array_keys($this->specs) as $name) {
            $handle = $this->handles[$name] ?? null;
            if ($handle !== null) {
                $this->spawner->kill($handle);
            }
            $this->handles[$name] = null;
        }
    }

    /**
     * Diagnostic seam: return to the pre-adoption state.
     *
     * Kills before it forgets. An earlier revision dropped the handles without
     * killing, which is a trap: a single reset() from a host shutdown handler
     * would orphan every child by design, with nothing left to reap them.
     */
    public function reset(): void
    {
        $this->stop();

        $this->specs = [];
        $this->handles = [];
        $this->restarts = [];
        $this->nextAttemptAt = [];
        $this->lastExitCode = [];
        $this->backoff = [];
        $this->stopped = false;
    }

    /**
     * Backstop so a normal script end, an uncaught throw or a fatal error still
     * terminates the children instead of leaking a duplicate set.
     *
     * This cannot cover SIGKILL of the supervisor itself — no userland code runs
     * in that case — which is why SystemProcessSpawner also destructs: whichever
     * object the engine tears down first gets the chance to reap. A destructor
     * must never throw, so failures are swallowed.
     */
    public function __destruct()
    {
        try {
            $this->stop();
        } catch (\Throwable) {
            // Teardown is best-effort; there is no caller left to report to.
        }
    }

    private function start(string $name): void
    {
        $spec = $this->specs[$name];

        try {
            $this->handles[$name] = $this->spawner->spawn($spec);
            $this->backoff[$name] = 0;
        } catch (\Throwable $e) {
            // A process that cannot even be spawned still gets the backoff
            // ladder — otherwise a permanently broken command would spin-restart
            // as fast as the tick allows.
            $this->handles[$name] = null;
            $this->restarts[$name] = ($this->restarts[$name] ?? 0) + 1;
            $this->backoff[$name] = $spec->backoffFor($this->restarts[$name]);
            $this->nextAttemptAt[$name] = ($this->clock)() + $this->backoff[$name];
            $this->logger->error('teleframe.supervisor: cannot start "'.$name.'" — '.$e->getMessage());

            return;
        }

        $this->logger->info('teleframe.supervisor: started "'.$name.'" as pid '.$this->handles[$name]->pid);
    }

    private function onDeath(string $name): void
    {
        $handle = $this->handles[$name];
        $code = $handle !== null ? $this->spawner->exitCode($handle) : null;

        $this->lastExitCode[$name] = $code;
        $this->handles[$name] = null;
        $this->restarts[$name] = ($this->restarts[$name] ?? 0) + 1;
        $this->backoff[$name] = $this->specs[$name]->backoffFor($this->restarts[$name]);
        $this->nextAttemptAt[$name] = ($this->clock)() + $this->backoff[$name];

        $this->logger->warning(sprintf(
            'teleframe.supervisor: "%s" died (exit %s); restart #%d in %ds',
            $name,
            $code === null ? 'unknown' : (string) $code,
            $this->restarts[$name],
            $this->backoff[$name],
        ));
    }

    private function isExhausted(ProcessSpec $spec): bool
    {
        return $spec->maxRestarts > 0 && ($this->restarts[$spec->name] ?? 0) >= $spec->maxRestarts;
    }
}
