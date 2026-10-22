<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Supervisor;

use RuntimeException;

/**
 * The shipped REAL process seam: proc_open + pipes, no shell.
 *
 * argv is passed as an array (PHP 7.4+ proc_open), so a process name or
 * argument containing shell metacharacters is never interpreted by a shell —
 * the supervisor runs commands, it does not evaluate them.
 *
 * Construction is INERT: the constructor allocates an id counter and nothing
 * else. proc_open happens only inside spawn(), which is only reached once the
 * Supervisor has decided a process should be running — so this class is safe
 * to bind at container boot and the standalone-smoke gate can construct it
 * without opening anything.
 *
 * Child stdio is /dev/null (a supervised worker is a daemon, not a terminal;
 * nothing is piped back to the supervisor — the ops surface reads the Redis
 * state instead, see OpsReadModel).
 */
final class SystemProcessSpawner implements ProcessSpawnerContract
{
    private int $nextId = 1;

    /**
     * Handle id => bookkeeping. Populated only by spawn(); isAlive() refreshes
     * the liveness bit from proc_get_status() and caches the exit code the
     * first time the child is seen down (proc_get_status() reports exitcode
     * -1 after the first call, so a single capture is deliberate).
     *
     * The five keys are written together by spawn() and are the whole entry:
     * `name`/`pid` are the ProcessHandle identity the destructor needs to
     * rebuild a handle at teardown, so they are part of the declared shape,
     * not optional extras.
     *
     * @var array<int, array{resource: mixed, running: bool, exitCode: int|null, name: string, pid: int}>
     */
    private array $processes = [];

    public function spawn(ProcessSpec $spec): ProcessHandle
    {
        $descriptors = [
            0 => ['file', '/dev/null', 'r'],
            1 => ['file', '/dev/null', 'a'],
            2 => ['file', '/dev/null', 'a'],
        ];

        $env = $spec->env === [] ? null : [...$this->inheritedEnv(), ...$spec->env];

        $pipes = [];
        $resource = @proc_open($spec->command, $descriptors, $pipes, null, $env);

        if (! is_resource($resource)) {
            // Name and reason only. argv is host-supplied data and a supervised
            // spec may carry a credential in it; the caller (Supervisor::start)
            // logs this message on every failed start of the restart loop, so
            // anything in here is written to the log over and over. $spec->env
            // is likewise never rendered.
            throw new RuntimeException(
                'Cannot start supervised process "'.$spec->name.'" ('
                .count($spec->command).' argv entries): proc_open failed.'
            );
        }

        $handle = new ProcessHandle(
            id: $this->nextId++,
            name: $spec->name,
            pid: $this->status($resource)['pid'],
            opaque: $resource,
        );

        $this->processes[$handle->id] = [
            'resource' => $resource,
            'running' => true,
            'exitCode' => null,
            'name' => $spec->name,
            'pid' => $handle->pid,
        ];

        return $handle;
    }

    public function isAlive(ProcessHandle $handle): bool
    {
        $entry = $this->processes[$handle->id] ?? null;
        if ($entry === null || $entry['running'] === false) {
            return false;
        }

        $status = $this->status($entry['resource']);
        if ($status['running']) {
            return true;
        }

        $this->processes[$handle->id]['running'] = false;
        $this->processes[$handle->id]['exitCode'] = $this->exitCodeFrom($status['exitcode']);

        return false;
    }

    public function kill(ProcessHandle $handle): void
    {
        $entry = $this->processes[$handle->id] ?? null;
        if ($entry === null || $entry['running'] === false) {
            return;
        }

        // status() first: it refreshes the cached running bit, and a second
        // proc_get_status() call would report exitcode -1.
        $this->status($entry['resource']);
        @proc_terminate($entry['resource']);

        $deadline = microtime(true) + 5.0;
        $status = $this->status($entry['resource']);
        while ($status['running'] && microtime(true) < $deadline) {
            usleep(50_000);
            $status = $this->status($entry['resource']);
        }

        if ($status['running']) {
            @proc_terminate($entry['resource'], 9); // SIGKILL: last resort, then reap.
            $status = $this->status($entry['resource']);
        }

        $this->processes[$handle->id]['running'] = false;
        $this->processes[$handle->id]['exitCode'] = $this->exitCodeFrom($status['exitcode']);
        $this->reap($handle->id);
    }

    public function exitCode(ProcessHandle $handle): ?int
    {
        $entry = $this->processes[$handle->id] ?? null;
        if ($entry === null) {
            return null;
        }

        if ($entry['running'] === true) {
            $this->isAlive($handle);
            $entry = $this->processes[$handle->id];
        }

        if ($entry['running'] === true) {
            return null;
        }

        $this->reap($handle->id);

        return $entry['exitCode'];
    }

    /**
     * One normalized read of proc_get_status(). A dead or already-reaped
     * resource reports as not running with no exit code, so a caller never has
     * to re-check the resource it was handed.
     *
     * @return array{pid: int, running: bool, exitcode: int}
     */
    private function status(mixed $resource): array
    {
        if (! is_resource($resource)) {
            return ['pid' => 0, 'running' => false, 'exitcode' => -1];
        }

        $status = @proc_get_status($resource);

        return [
            'pid' => (int) $status['pid'],
            'running' => (bool) $status['running'],
            'exitcode' => (int) $status['exitcode'],
        ];
    }

    /**
     * proc_get_status() reports -1 for "unknown"; normalize to null so the ops
     * row can say "the exit code was not observed" instead of printing -1.
     */
    private function exitCodeFrom(int $code): ?int
    {
        return $code >= 0 ? $code : null;
    }

    /**
     * Last-resort backstop: this object owns the proc_open resources, so
     * whoever holds the handle must release them.
     *
     * Without this, any exit path that never reaches Supervisor::stop() —
     * an uncaught throw, a fatal error, an OOM kill, a normal script end,
     * or a host without ext-pcntl — leaves every supervised child running
     * and unreaped. A supervisor that leaks its children on abnormal exit
     * is worse than the config it replaced: the next start adopts nothing
     * and spawns a SECOND set, and two consumers then fight over the same
     * Redis stream.
     *
     * A destructor must never throw, so every failure is swallowed here:
     * the process is being torn down and there is no caller left to tell.
     */
    public function __destruct()
    {
        // Iterate the entries themselves: every key written by spawn() is
        // declared, so the id, the name and the pid are all present and
        // non-null by construction — no defensive fallback can ever be
        // reached here. kill() (and its reap()) writes through the property,
        // never through this foreach, so the copy being walked is stable.
        foreach ($this->processes as $id => $entry) {
            if ($entry['running'] !== true) {
                continue;
            }

            try {
                $this->kill(new ProcessHandle(
                    $id,
                    $entry['name'],
                    $entry['pid'],
                ));
            } catch (\Throwable) {
                // Teardown is best-effort by definition. A child we could not
                // terminate cleanly is still reaped below if its status ever
                // reports exited; nothing further is safe to do from here.
            }
        }
    }

    /**
     * Reap an exited child exactly once (proc_close on an already-reaped
     * resource is a warning, not a no-op).
     */
    private function reap(int $id): void
    {
        $entry = $this->processes[$id] ?? null;
        if ($entry === null || $entry['resource'] === null) {
            return;
        }

        if (is_resource($entry['resource'])) {
            @proc_close($entry['resource']);
        }

        $this->processes[$id]['resource'] = null;
    }

    /**
     * @return array<string, string>
     */
    private function inheritedEnv(): array
    {
        $env = [];
        foreach ($_SERVER as $key => $value) {
            if (is_string($key) && is_string($value)) {
                $env[$key] = $value;
            }
        }

        return $env;
    }
}
