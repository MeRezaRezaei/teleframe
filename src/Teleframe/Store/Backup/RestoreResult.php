<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Backup;

/**
 * RestoreResult (remainder design §8 wave-2 DatabaseRestorer line) — one
 * restore outcome: the driver/database identity, the run's started/
 * finished unix timestamps, the restore process exit code, the last
 * 4 KiB of stderr, and the byte count fed into the client over stdin.
 * Only ever SUCCESSFUL restores reach a RestoreResult (a non-zero exit
 * throws BackupException in the restorer) — exitCode is still carried so
 * async replay can verify the recorded truth.
 *
 * bytesRestored is the source's observed size: for the built-in runner
 * the exact stdin fan-in byte count, for an injected fake-runner the
 * source's fstat/filesize (the fake never sees the bytes).
 *
 * Pure data — readonly ctor, toArray(). Zero regex.
 *
 * Provenance:
 * docs/superpowers/plans/2026-09-24-backup-store-remainder-design.md §8
 * (wave-2 DatabaseRestorer line) + mirror sibling
 * src/Teleframe/Store/Backup/DumpResult.php.
 */
final class RestoreResult
{
    public function __construct(
        public readonly string $driver,
        public readonly string $database,
        public readonly int $startedAt,
        public readonly int $finishedAt,
        public readonly int $exitCode,
        public readonly string $stderrTail,
        public readonly int $bytesRestored,
    ) {}

    /** @return array{driver: string, database: string, startedAt: int, finishedAt: int, exitCode: int, stderrTail: string, bytesRestored: int} */
    public function toArray(): array
    {
        return [
            'driver' => $this->driver,
            'database' => $this->database,
            'startedAt' => $this->startedAt,
            'finishedAt' => $this->finishedAt,
            'exitCode' => $this->exitCode,
            'stderrTail' => $this->stderrTail,
            'bytesRestored' => $this->bytesRestored,
        ];
    }
}
