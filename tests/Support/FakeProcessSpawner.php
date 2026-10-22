<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Support;

use MeRezaRezaei\Teleframe\Supervisor\ProcessHandle;
use MeRezaRezaei\Teleframe\Supervisor\ProcessSpawnerContract;
use MeRezaRezaei\Teleframe\Supervisor\ProcessSpec;
use RuntimeException;

/**
 * Plain-PHP process seam double: NO real process is ever started.
 *
 * A spawned handle stays "running" until the test kills it with
 * `crash($name, $exitCode)` — which is exactly the supervisor's failure
 * signal — so a full crash → backoff → restart cycle is exercised in
 * microseconds with no fork, no shell and no wiring.
 */
final class FakeProcessSpawner implements ProcessSpawnerContract
{
    public int $spawns = 0;

    public int $kills = 0;

    /** @var list<ProcessSpec> */
    public array $spawned = [];

    /** @var array<int, array{spec: ProcessSpec, running: bool, exitCode: int|null}> */
    private array $handles = [];

    private int $nextId = 1;

    /** When set, spawn() throws for this process name (an unstartable command). */
    public ?string $failSpawnFor = null;

    public function spawn(ProcessSpec $spec): ProcessHandle
    {
        if ($this->failSpawnFor === $spec->name) {
            throw new RuntimeException('fake spawner: cannot start "'.$spec->name.'"');
        }

        $id = $this->nextId++;
        $handle = new ProcessHandle(id: $id, name: $spec->name, pid: 4000 + $id);
        $this->handles[$id] = ['spec' => $spec, 'running' => true, 'exitCode' => null];
        $this->spawns++;
        $this->spawned[] = $spec;

        return $handle;
    }

    public function isAlive(ProcessHandle $handle): bool
    {
        return $this->handles[$handle->id]['running'] ?? false;
    }

    public function kill(ProcessHandle $handle): void
    {
        if ($this->handles[$handle->id]['running'] ?? false) {
            $this->handles[$handle->id]['running'] = false;
            $this->handles[$handle->id]['exitCode'] = null;
            $this->kills++;
        }
    }

    public function exitCode(ProcessHandle $handle): ?int
    {
        return $this->handles[$handle->id]['exitCode'] ?? null;
    }

    /**
     * The newest handle for a process name, so a test can crash the live one.
     */
    public function latest(string $name): ProcessHandle
    {
        $id = null;
        foreach ($this->handles as $handleId => $entry) {
            if ($entry['spec']->name === $name) {
                $id = $handleId;
            }
        }

        if ($id === null) {
            throw new RuntimeException('fake spawner: "'.$name.'" was never spawned');
        }

        return new ProcessHandle(id: $id, name: $name, pid: 4000 + $id);
    }

    /** The process died with this exit code — the supervisor's restart trigger. */
    public function crash(string $name, int $exitCode = 1): void
    {
        $handle = $this->latest($name);
        $this->handles[$handle->id]['running'] = false;
        $this->handles[$handle->id]['exitCode'] = $exitCode;
    }

    public function howManyTimesSpawned(string $name): int
    {
        $count = 0;
        foreach ($this->spawned as $spec) {
            if ($spec->name === $name) {
                $count++;
            }
        }

        return $count;
    }
}
