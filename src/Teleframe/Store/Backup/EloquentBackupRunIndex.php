<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Backup;

use MeRezaRezaei\Teleframe\Store\Index\Models\TfStoreBackupRun;

/**
 * Eloquent BackupRunIndexContract (remainder design §2): thin proxies over
 * the tf_store_backup_runs model — the only Laravel-touching class in the
 * slice (the contract's allowed exception: the index uses the model, period).
 * Natural key {account_id, object_name} via updateOrCreate (idempotent
 * re-runs), timestamps handled by the model, casts applied by the model.
 * No caching tricks in the slice — every call is a real query.
 *
 * Provenance: docs/superpowers/plans/2026-09-24-backup-store-remainder-design.md
 * (§6 index rows) + migration 2026_09_24_000100.
 */
final class EloquentBackupRunIndex implements BackupRunIndexContract
{
    public function upsertRun(array $row): void
    {
        $accountId = (int) ($row['account_id'] ?? 0);
        $objectName = (string) ($row['object_name'] ?? '');
        unset($row['id'], $row['account_id'], $row['object_name']);

        TfStoreBackupRun::query()->updateOrCreate(
            ['account_id' => $accountId, 'object_name' => $objectName],
            $row,
        );
    }

    public function listRuns(int $accountId, ?string $bucket, int $limit): array
    {
        $query = TfStoreBackupRun::query()->where('account_id', $accountId);
        if ($bucket !== null) {
            $query->where('bucket', $bucket);
        }

        // Rows are read through the model's magic accessors so the casts
        // apply (bytes/chunks/heartbeat_at_unix → int); each run is mapped
        // to the DB-column-keyed array shape the contract returns. No
        // caching tricks in the slice — every call is a real query.
        $rows = [];
        foreach ($query->orderByDesc('id')->limit($limit)->get() as $run) {
            $heartbeat = $run->heartbeat_at_unix;
            $rows[] = [
                'id' => (int) $run->id,
                'account_id' => (int) $run->account_id,
                'bucket' => (string) $run->bucket,
                'peer_type' => $run->peer_type === null ? null : (string) $run->peer_type,
                'driver' => (string) $run->driver,
                'database' => (string) $run->database,
                'object_name' => (string) $run->object_name,
                'bytes' => (int) $run->bytes,
                'chunks' => (int) $run->chunks,
                'sha256' => (string) $run->sha256,
                'mode' => (string) $run->mode,
                'status' => (string) $run->status,
                'heartbeat_at_unix' => $heartbeat === null ? null : (int) $heartbeat,
            ];
        }

        return $rows;
    }

    public function deleteRun(int $accountId, string $objectName): void
    {
        TfStoreBackupRun::query()
            ->where('account_id', $accountId)
            ->where('object_name', $objectName)
            ->delete();
    }
}
