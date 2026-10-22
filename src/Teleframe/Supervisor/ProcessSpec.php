<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Supervisor;

use InvalidArgumentException;

/**
 * One supervised process: the exact argv to run, the environment overrides
 * it needs, and its restart policy.
 *
 * A spec is DATA — building one opens no process, dials nothing and reads no
 * config. `ProcessCatalog::specFor()` builds the specs for the long-running
 * commands the package ships; a host with its own long-running command builds
 * its own spec and hands it to the Supervisor.
 */
final class ProcessSpec
{
    /**
     * @param  string  $name  stable identifier (the catalog key, e.g. 'daemon'); used as the ops row id.
     * @param  list<string>  $command  argv, element 0 is the executable (never a shell string — no shell is involved).
     * @param  array<string, string>  $env  environment overrides merged over the inherited environment.
     * @param  int  $restartDelaySeconds  base backoff before the first restart.
     * @param  int  $maxBackoffSeconds  ceiling for the exponential ladder.
     * @param  int  $maxRestarts  restarts allowed per process; 0 = unlimited.
     */
    public function __construct(
        public readonly string $name,
        public readonly array $command,
        public readonly array $env = [],
        public readonly int $restartDelaySeconds = 5,
        public readonly int $maxBackoffSeconds = 300,
        public readonly int $maxRestarts = 0,
    ) {
        if ($name === '') {
            throw new InvalidArgumentException('A supervised process needs a non-empty name.');
        }

        if ($command === [] || $command[0] === '') {
            throw new InvalidArgumentException(
                'Process "'.$name.'" has no command — argv[0] must be the executable to run.'
            );
        }
    }

    /**
     * Build a spec that runs one artisan command in its own process.
     *
     * @param  list<string>  $artisanArgs  the artisan command and its arguments, e.g. ['teleframe:daemon'].
     * @param  array<string, string>  $env
     */
    public static function artisan(
        string $name,
        string $phpBinary,
        string $artisanPath,
        array $artisanArgs,
        array $env = [],
        int $restartDelaySeconds = 5,
        int $maxBackoffSeconds = 300,
        int $maxRestarts = 0,
    ): self {
        return new self(
            name: $name,
            command: [$phpBinary, $artisanPath, ...$artisanArgs],
            env: $env,
            restartDelaySeconds: $restartDelaySeconds,
            maxBackoffSeconds: $maxBackoffSeconds,
            maxRestarts: $maxRestarts,
        );
    }

    /**
     * Exponential backoff for the nth restart (1-based), capped at
     * maxBackoffSeconds. Integer math only, no floats.
     */
    public function backoffFor(int $restartNumber): int
    {
        if ($restartNumber < 1) {
            return 0;
        }

        $delay = $this->restartDelaySeconds * (2 ** min(20, $restartNumber - 1));

        return (int) min($this->maxBackoffSeconds, $delay);
    }
}
