<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Backup;

/**
 * In-memory BackupRunIndexContract (remainder design §2): rows keyed by
 * account → object_name, with an internal monotonic id so listRuns orders
 * newest-first exactly like the Eloquent table's id. Used by tests, the
 * standalone smoke, and any sandbox where no database surface exists —
 * no container, no persistence, upserts replace whole rows.
 *
 * Provenance: docs/superpowers/plans/2026-09-24-backup-store-remainder-design.md.
 */
final class MemoryBackupRunIndex implements BackupRunIndexContract
{
    /** @var array<int, array<string, array<string, mixed>>> account → object_name → row */
    private array $runs = [];

    private int $nextId = 1;

    public function upsertRun(array $row): void
    {
        $accountId = (int) ($row['account_id'] ?? 0);
        $objectName = (string) ($row['object_name'] ?? '');
        $row['id'] = (int) ($row['id'] ?? $this->nextId++);
        $row['account_id'] = $accountId;
        $row['object_name'] = $objectName;
        $this->runs[$accountId][$objectName] = $row;
    }

    public function listRuns(int $accountId, ?string $bucket, int $limit): array
    {
        $rows = array_values($this->runs[$accountId] ?? []);
        if ($bucket !== null) {
            $rows = array_values(array_filter(
                $rows,
                fn (array $row): bool => ($row['bucket'] ?? null) === $bucket,
            ));
        }

        usort(
            $rows,
            fn (array $a, array $b): int => (int) ($b['id'] ?? 0) <=> (int) ($a['id'] ?? 0),
        );

        return array_slice($rows, 0, $limit);
    }

    public function deleteRun(int $accountId, string $objectName): void
    {
        unset($this->runs[$accountId][$objectName]);
    }
}
