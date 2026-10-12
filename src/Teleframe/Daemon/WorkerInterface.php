<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Daemon;

/**
 * What Daemon supervises: one runnable account loop with a stop hook.
 * AccountWorker is the production implementation; the daemon's
 * workerFactory closure adapts it (binding its sink — bus wiring lives
 * in the host bootstrap, docs/bus.md) and tests supply fakes.
 */
interface WorkerInterface
{
    /**
     * Run until $shouldStop() reports true (null = run until the worker
     * ends on its own). Returning normally = a healthy end of shift;
     * throwing = the account needs isolation.
     *
     * @param (callable(): bool)|null $shouldStop
     */
    public function run(?callable $shouldStop = null): void;
}
