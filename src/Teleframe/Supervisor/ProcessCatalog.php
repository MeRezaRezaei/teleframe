<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Supervisor;

/**
 * The closed catalog of long-running processes the package SHIPS.
 *
 * These are the commands under `src/Laravel/Console/` that run forever in the
 * foreground by design — the package's own answer to "the host should not
 * have to wire a worker pool":
 *
 *   daemon       teleframe:daemon        multi-account poll → stream → ingest
 *   ingest       teleframe:ingest        stream → Postgres truth, standalone
 *   tstore-work  teleframe:tstore work   async upload queue drain
 *   relay        teleframe:relay         obfuscated2 relay terminal
 *
 * A host that runs a subset configures which names; a host with its OWN
 * long-running command binds a ProcessSpec list under
 * SupervisorSupport::SPECS_KEY rather than editing the package.
 *
 * Construction is inert — a catalog is a name → argv map, no process, no
 * config, no container.
 */
final class ProcessCatalog
{
    /** Container seam: list<ProcessSpec> — a host's own supervised processes. */
    public const SPECS_KEY = 'teleclient.supervisor.specs';

    /** The shipped long-running processes: name => artisan argv. */
    private const SHIPPED = [
        'daemon' => ['teleframe:daemon'],
        'ingest' => ['teleframe:ingest'],
        'tstore-work' => ['teleframe:tstore', 'work'],
        'relay' => ['teleframe:relay'],
    ];

    /**
     * @param  array<string, list<string>>  $overrides  extra name => artisan argv rows (host-extended catalog).
     * @param  array<string, array<string, string>>  $env  per-process environment overrides, keyed by process name.
     */
    public function __construct(
        private readonly array $overrides = [],
        private readonly array $env = [],
        private readonly int $restartDelaySeconds = 5,
        private readonly int $maxBackoffSeconds = 300,
        private readonly int $maxRestarts = 0,
    ) {}

    /**
     * Every process name this catalog can supervise, shipped first, sorted.
     *
     * @return list<string>
     */
    public function names(): array
    {
        $names = array_keys(array_merge(self::SHIPPED, $this->overrides));
        sort($names);

        return $names;
    }

    public function has(string $name): bool
    {
        return isset(self::SHIPPED[$name]) || isset($this->overrides[$name]);
    }

    /**
     * The artisan argv for a name.
     *
     * @return list<string>
     *
     * @throws UnknownProcessException When the name is not in the catalog.
     */
    public function commandFor(string $name): array
    {
        if (! $this->has($name)) {
            throw UnknownProcessException::for($name, $this->names());
        }

        return self::SHIPPED[$name] ?? $this->overrides[$name];
    }

    /**
     * Build the ProcessSpec for a name, wiring the catalog's artisan binary
     * and artisan path. Throws (loudly) on an unknown name.
     *
     * @throws UnknownProcessException When the name is not in the catalog.
     */
    public function specFor(
        string $name,
        string $phpBinary = PHP_BINARY,
        string $artisanPath = 'artisan',
    ): ProcessSpec {
        return ProcessSpec::artisan(
            name: $name,
            phpBinary: $phpBinary,
            artisanPath: $artisanPath,
            artisanArgs: $this->commandFor($name),
            env: $this->env[$name] ?? [],
            restartDelaySeconds: $this->restartDelaySeconds,
            maxBackoffSeconds: $this->maxBackoffSeconds,
            maxRestarts: $this->maxRestarts,
        );
    }

    /**
     * Build specs for a list of names, in the order given.
     *
     * The FIRST unknown name aborts the whole batch — a partial supervision
     * set is exactly the silent failure this module refuses to produce.
     *
     * @param  list<string>  $names
     * @return list<ProcessSpec>
     *
     * @throws UnknownProcessException
     */
    public function specsFor(array $names, string $phpBinary = PHP_BINARY, string $artisanPath = 'artisan'): array
    {
        return array_map(
            fn (string $name): ProcessSpec => $this->specFor($name, $phpBinary, $artisanPath),
            $names,
        );
    }
}
