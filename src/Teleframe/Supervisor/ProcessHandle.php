<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Supervisor;

/**
 * The opaque handle the spawner hands back for a started process.
 *
 * Deliberately thin: the Supervisor only ever passes it back to the spawner
 * (isAlive / kill / exitCode), so a real implementation may stash its own
 * process resources in `$opaque` without the Supervisor knowing what they are.
 */
final class ProcessHandle
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly int $pid,
        public readonly mixed $opaque = null,
    ) {}
}
