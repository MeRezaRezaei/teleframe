<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Backup;

/**
 * Backup run ledger (remainder design §2 BackupRunIndexContract row, §4
 * "managing indexes", §6 index rows): the tf_store_backup_runs projection
 * of every dump run — identity (account/bucket/object_name), the stored
 * object's observed truth (driver, database, bytes, chunks, sha256, mode),
 * status (ok|queued), and heartbeat_at_unix. One implementation is
 * Eloquent-backed (the shipped truth table), one is a memory array
 * (tests/standalone/unconfigured). Retention (gap G4) = listRuns +
 * deleteRun; the orchestrator owns the call order, never the container —
 * rows cross the boundary as arrays keyed by the DB column names.
 *
 * Provenance: docs/superpowers/plans/2026-09-24-backup-store-remainder-design.md.
 */
interface BackupRunIndexContract
{
    /**
     * Insert or replace the run row for {account_id, object_name}
     * (idempotent re-runs — a run's identity is its object name).
     *
     * @param  array<string, mixed>  $row  account_id, bucket, driver,
     *                                     database, object_name, bytes,
     *                                     chunks, sha256, mode, status,
     *                                     heartbeat_at_unix
     */
    public function upsertRun(array $row): void;

    /**
     * Runs for one account, newest first, limited (bucket null = all
     * buckets). Rows keep the DB column names as keys.
     *
     * @return list<array<string, mixed>>
     */
    public function listRuns(int $accountId, ?string $bucket, int $limit): array;

    /** Forget one run by its object name (prune helper, wave 2 --prune). */
    public function deleteRun(int $accountId, string $objectName): void;
}
