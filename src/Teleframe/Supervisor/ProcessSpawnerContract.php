<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Supervisor;

use RuntimeException;

/**
 * The process seam the Supervisor is built over: spawn, liveness, kill,
 * exit code. INJECTED, never assumed.
 *
 * The Supervisor itself never calls proc_open / pcntl_fork / posix_kill: it
 * only speaks this contract, which is why it stays plain-PHP-constructible
 * and why tests can supervise crashes without a single real process. The
 * shipped real implementation is `SystemProcessSpawner`; hosts and tests
 * substitute their own.
 */
interface ProcessSpawnerContract
{
    /**
     * Start the process. Called only when the supervisor decides the process
     * should be running — never at Supervisor construction.
     *
     * @throws RuntimeException When the process cannot be started at all.
     */
    public function spawn(ProcessSpec $spec): ProcessHandle;

    /** Is the process still running? Must not block. */
    public function isAlive(ProcessHandle $handle): bool;

    /**
     * Terminate the process (polite signal first, then the hard one).
     * Killing an already-dead process is a no-op, not an error.
     */
    public function kill(ProcessHandle $handle): void;

    /**
     * The process's exit code once it is known, else null (still running, or
     * never observed to have exited).
     */
    public function exitCode(ProcessHandle $handle): ?int;
}
