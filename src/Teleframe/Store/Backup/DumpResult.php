<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Backup;

/**
 * DumpResult (remainder design §2/§3) — one dump outcome: the spooled
 * snapshot's path + observed byte size, the driver/database identity,
 * the run's started/finished unix timestamps, the dump process exit
 * code, and the last 4 KiB of stderr. Only ever SUCCESSFUL dumps reach
 * a DumpResult (a non-zero exit throws BackupException in the dumper) —
 * exitCode is still carried so async replay can verify the recorded
 * truth.
 *
 * objectName() is the immutable append-only object name the run stores
 * under: {database}-{Y-m-d-His}.sql — unique per run (the started
 * timestamp), so a dump set is immutable history (design §4 object name).
 *
 * Pure data — readonly ctor, objectName() (concatenation + gmdate),
 * toArray(). Zero regex.
 *
 * Provenance:
 * docs/superpowers/plans/2026-09-24-backup-store-remainder-design.md §2
 * (DumpResult row), §3 (stderr tail cap), §4 (object name ruling).
 */
final class DumpResult
{
    public function __construct(
        public readonly string $path,
        public readonly int $bytes,
        public readonly string $driver,
        public readonly string $database,
        public readonly int $startedAt,
        public readonly int $finishedAt,
        public readonly int $exitCode,
        public readonly string $stderrTail,
    ) {}

    /**
     * The immutable object name for this run:
     * {database}-{Y-m-d-His}.sql (startedAt is the run's identity moment).
     */
    public function objectName(): string
    {
        return $this->database.'-'.gmdate('Y-m-d-His', $this->startedAt).'.sql';
    }

    /** @return array{path: string, bytes: int, driver: string, database: string, startedAt: int, finishedAt: int, exitCode: int, stderrTail: string} */
    public function toArray(): array
    {
        return [
            'path' => $this->path,
            'bytes' => $this->bytes,
            'driver' => $this->driver,
            'database' => $this->database,
            'startedAt' => $this->startedAt,
            'finishedAt' => $this->finishedAt,
            'exitCode' => $this->exitCode,
            'stderrTail' => $this->stderrTail,
        ];
    }
}
