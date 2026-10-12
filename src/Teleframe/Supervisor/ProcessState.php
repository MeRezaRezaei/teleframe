<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Supervisor;

/**
 * The observable state of ONE supervised process — the row the ops surface and
 * the command's report table read. A snapshot value, not a live handle: it is
 * safe to json_encode() straight into the ops JSON.
 */
final class ProcessState
{
    public const RUNNING = 'running';

    public const RESTARTING = 'restarting';

    public const BACKOFF = 'backoff';

    public const EXHAUSTED = 'exhausted';

    public const STOPPED = 'stopped';

    public function __construct(
        public readonly string $name,
        public readonly string $status,
        public readonly ?int $pid = null,
        public readonly int $restarts = 0,
        public readonly ?int $lastExitCode = null,
        public readonly int $backoffSeconds = 0,
        public readonly int $nextAttemptAt = 0,
    ) {}

    /**
     * @return array{name: string, status: string, pid: int|null, restarts: int, last_exit_code: int|null, backoff_seconds: int, next_attempt_at: int}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'status' => $this->status,
            'pid' => $this->pid,
            'restarts' => $this->restarts,
            'last_exit_code' => $this->lastExitCode,
            'backoff_seconds' => $this->backoffSeconds,
            'next_attempt_at' => $this->nextAttemptAt,
        ];
    }
}
