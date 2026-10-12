<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Console;

use Illuminate\Console\Command;
use MeRezaRezaei\Teleframe\Supervisor\OpsReadModel;
use MeRezaRezaei\Teleframe\Supervisor\ProcessCatalog;
use MeRezaRezaei\Teleframe\Supervisor\ProcessSpawnerContract;
use MeRezaRezaei\Teleframe\Supervisor\ProcessSpec;
use MeRezaRezaei\Teleframe\Supervisor\ProcessState;
use MeRezaRezaei\Teleframe\Supervisor\Supervisor;
use MeRezaRezaei\Teleframe\Supervisor\SystemProcessSpawner;
use MeRezaRezaei\Teleframe\Supervisor\UnknownProcessException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * artisan teleframe:supervise — the package's OWN process supervisor, so the
 * host wires nothing.
 *
 * Before this command the supported path was `docs/scaling.md` handing the
 * host a `config/horizon.php` to paste plus a polling job to write — the
 * exact delegation the user's own words reject. After it, ONE command is the
 * whole integration:
 *
 *     php artisan teleframe:supervise                 # every configured process
 *     php artisan teleframe:supervise --process=daemon --process=ingest
 *     php artisan teleframe:supervise --once          # one tick, print the report, exit
 *     php artisan teleframe:supervise --status        # ops JSON, no process started
 *
 * Which processes run comes from `config('teleframe.supervisor.processes')`,
 * which ships with the package. An unknown `--process` name is a LOUD
 * failure (UnknownProcessException → FAILURE), never a silent skip: a
 * supervisor that supervises nothing looks exactly like a healthy one.
 *
 * Seams: ProcessCatalog::SPECS_KEY (a host's own ProcessSpec list) and
 * OpsReadModel::REDIS_KEY. The spawner seam is resolved lazily so `--status`
 * and `--help` never open a process.
 */
final class SuperviseCommand extends Command
{
    protected $signature = 'teleframe:supervise
        {--process=* : Process name(s) to supervise (default: config teleframe.supervisor.processes)}
        {--once : Run one supervision tick, print the report and exit}
        {--status : Print the operations snapshot as JSON and exit (no process is started)}
        {--ticks= : Bound the supervision loop to N ticks (diagnostics; default: run until stopped)}';

    /** Supervision tick: how often liveness is polled. */
    private const TICK_SECONDS = 5;

    protected $description = 'Supervise the package\'s long-running commands (daemon, ingest, tstore work, relay) and restart them with backoff';

    private bool $shouldStop = false;

    public function handle(): int
    {
        $catalog = $this->catalog();

        try {
            $names = $this->processNames($catalog);
            $specs = $this->specsFor($names, $catalog);
        } catch (UnknownProcessException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        if ($specs === []) {
            $this->components->error(
                'No processes to supervise: set teleframe.supervisor.processes (or pass --process=).'
            );

            return self::FAILURE;
        }

        $supervisor = new Supervisor($this->spawner(), logger: $this->logger());

        if ((bool) $this->option('status')) {
            // The specs have to be REGISTERED before report() reads them.
            //
            // `supervise($specs)` is what normally registers them, and this
            // branch returned before ever reaching it — so `report()` iterated
            // an empty set and `--status` printed `supervised: []` for EVERY
            // configuration, while `sources.supervisor` still read ok. A
            // status command that reports nothing is worse than one that does
            // not exist, and this contradicted the command's own docblock
            // claim that the CLI and the page can never disagree.
            foreach ($specs as $spec) {
                $supervisor->register($spec);
            }

            return $this->status($supervisor);
        }

        $this->installSignalHandlers();

        $this->components->info(sprintf(
            'Supervising %s (tick %ds, stop with Ctrl-C).',
            implode(', ', $names),
            self::TICK_SECONDS,
        ));

        $rows = $supervisor->supervise(
            $specs,
            tickSeconds: self::TICK_SECONDS,
            maxTicks: $this->option('once') ? 1 : $this->maxTicks(),
            shouldContinue: fn (): bool => ! $this->shouldStop,
        );

        $this->render($rows);
        $supervisor->stop();

        return self::SUCCESS;
    }

    /**
     * The ops read-model as JSON — the same snapshot the dashboard section
     * renders, so the CLI and the page can never disagree.
     */
    private function status(Supervisor $supervisor): int
    {
        $ops = new OpsReadModel(
            redis: $this->laravel->bound(OpsReadModel::REDIS_KEY) ? $this->laravel->make(OpsReadModel::REDIS_KEY) : null,
            supervised: static fn (): array => array_map(
                static fn (ProcessState $row): array => $row->toArray(),
                $supervisor->report(),
            ),
        );

        $this->line($ops->toJson(JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }

    /**
     * @param  list<ProcessState>  $rows
     */
    private function render(array $rows): void
    {
        if ($rows === []) {
            return;
        }

        $this->table(
            ['process', 'status', 'pid', 'restarts', 'last exit', 'backoff'],
            array_map(static fn (ProcessState $row): array => [
                $row->name,
                $row->status,
                $row->pid === null ? '-' : (string) $row->pid,
                (string) $row->restarts,
                $row->lastExitCode === null ? '-' : (string) $row->lastExitCode,
                $row->backoffSeconds > 0 ? $row->backoffSeconds.'s' : '-',
            ], $rows),
        );
    }

    /**
     * The names to supervise: --process, else the shipped config, else every
     * catalog name. A name the package cannot build — and that no host spec
     * claims — refuses here, loudly, rather than being dropped from the set.
     *
     * @return list<string>
     */
    private function processNames(ProcessCatalog $catalog): array
    {
        /** @var list<string> $requested */
        $requested = (array) $this->option('process');
        $requested = array_values(array_filter($requested, static fn (string $name): bool => $name !== ''));

        $configured = array_values(array_filter(
            (array) $this->laravel->make('config')->get('teleframe.supervisor.processes', []),
            static fn ($name): bool => is_string($name) && $name !== '',
        ));

        $hostNames = array_map(static fn (ProcessSpec $spec): string => $spec->name, $this->hostSpecs());
        $names = $requested !== [] ? $requested : ($configured !== [] ? $configured : $catalog->names());

        foreach ($names as $name) {
            if (! in_array($name, $hostNames, true)) {
                $catalog->commandFor($name);
            }
        }

        return $names;
    }

    /**
     * The host's own specs (ProcessCatalog::SPECS_KEY), or an empty list.
     *
     * @return list<ProcessSpec>
     */
    private function hostSpecs(): array
    {
        if (! $this->laravel->bound(ProcessCatalog::SPECS_KEY)) {
            return [];
        }

        $bound = $this->laravel->make(ProcessCatalog::SPECS_KEY);
        if (! is_array($bound)) {
            return [];
        }

        return array_values(array_filter($bound, static fn ($spec): bool => $spec instanceof ProcessSpec));
    }

    /**
     * @param  list<string>  $names
     * @return list<ProcessSpec>
     */
    private function specsFor(array $names, ProcessCatalog $catalog): array
    {
        $hostSpecs = $this->hostSpecs();
        $specs = array_values(array_filter(
            $hostSpecs,
            static fn (ProcessSpec $spec): bool => in_array($spec->name, $names, true),
        ));

        // Shipped catalog rows only for names the host did not claim.
        $missing = array_values(array_diff($names, array_map(static fn (ProcessSpec $s): string => $s->name, $hostSpecs)));

        return [...$specs, ...$catalog->specsFor(
            $missing,
            phpBinary: PHP_BINARY,
            artisanPath: $this->artisanPath(),
        )];
    }

    private function catalog(): ProcessCatalog
    {
        /** @var array<string, mixed> $config */
        $config = (array) $this->laravel->make('config')->get('teleframe.supervisor', []);

        return new ProcessCatalog(
            env: (array) ($config['env'] ?? []),
            restartDelaySeconds: (int) ($config['restart_delay_seconds'] ?? 5),
            maxBackoffSeconds: (int) ($config['max_backoff_seconds'] ?? 300),
            maxRestarts: (int) ($config['max_restarts'] ?? 0),
        );
    }

    private function spawner(): ProcessSpawnerContract
    {
        $key = 'teleclient.supervisor.spawner';
        if ($this->laravel->bound($key)) {
            $bound = $this->laravel->make($key);
            if ($bound instanceof ProcessSpawnerContract) {
                return $bound;
            }
        }

        return new SystemProcessSpawner;
    }

    private function artisanPath(): string
    {
        $argv = $_SERVER['argv'][0] ?? null;

        if (is_string($argv) && str_ends_with($argv, 'artisan') && is_file($argv)) {
            return $argv;
        }

        $base = $this->laravel->basePath('artisan');

        return is_file($base) ? $base : 'artisan';
    }

    private function maxTicks(): ?int
    {
        $ticks = $this->option('ticks');

        return is_numeric($ticks) && (int) $ticks > 0 ? (int) $ticks : null;
    }

    /**
     * Ctrl-C / SIGTERM stop the loop and let the supervisor kill its children
     * instead of orphaning them. Guarded: pcntl is optional.
     */
    private function installSignalHandlers(): void
    {
        if (! function_exists('pcntl_async_signals') || ! function_exists('pcntl_signal')) {
            return;
        }

        pcntl_async_signals(true);
        foreach ([SIGINT, SIGTERM] as $signal) {
            pcntl_signal($signal, function (): void {
                $this->shouldStop = true;
            });
        }
    }

    private function logger(): LoggerInterface
    {
        return $this->laravel->bound(LoggerInterface::class)
            ? $this->laravel->make(LoggerInterface::class)
            : new NullLogger;
    }
}
