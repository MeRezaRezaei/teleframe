<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Backup;

use Illuminate\Database\QueryException;
use InvalidArgumentException;
use MeRezaRezaei\Teleframe\Notify\NoShowWatchdog;
use MeRezaRezaei\Teleframe\Store\ObjectManifest;
use MeRezaRezaei\Teleframe\Store\StoreService;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * DatabaseBackupService (remainder design §2/§4/§9) — THE ORCHESTRATOR of
 * one backup run: dump → existing sealed put → run-ledger upsert → spool
 * release → heartbeat. Every port is injected (dumpers, the existing
 * StoreService::put seam, the run ledger, the shipped NoShowWatchdog), so
 * the same class runs against a live scope (Laravel wiring), the standalone
 * smoke, and offline tests (fakes for every port). Zero container calls.
 *
 * The chain, end to end (design §4):
 *   1. DatabaseDumper::dump() spools a seekable snapshot (DumpResult).
 *   2. $store->put(bucket, objectName, path, MODE_SEALED, passphrase,
 *      'application/sql', queued) — the EXISTING chunk/seal/upload path.
 *   3. BackupRunIndexContract::upsertRun() — the run-ledger row.
 *   4. $workspace->release() — inline runs unlink the spool; genuinely
 *      dispatched runs keep it (the queue worker owns it, putFromJob
 *      unlinks on success; design §3).
 *   5. $watchdog?->touch('backup') — SUCCESS only (null watchdog =
 *      no-op, unconfigured-safe; design §5).
 * On ANY failure: PSR-3 error log (when a logger is bound) + RETHROW —
 * logging never swallows (AGENTS.md), and a failed run must NOT touch the
 * heartbeat: that is exactly what the no-show alert detects (design §4/§5).
 *
 * RULING (target default): the core never fabricates a DatabaseTarget from
 * config — zero container calls. backup(null) therefore throws loud; the
 * Laravel provider builds the default target from
 * teleframe.store.backup.database.* and hands it in, and an explicit
 * target overrides it. Same for the bucket: the ctor's $bucket defaults to
 * '' → the config-free constant 'teleframe-backup:default' (design §7
 * bucket name); the provider passes the config-built name to override. And
 * same again for the spool dir: the ctor's $spoolDir defaults to '' →
 * DumpWorkspace's own sys_get_temp_dir()/teleframe-dumps fallback, while the
 * provider passes teleframe.store.backup.spool_dir to override. The core
 * still creates nothing until the first dump.
 *
 * RULING (mode + passphrase): sealed is the ONLY mode in the slice
 * (design §11 — config-merge refuses raw for non-owned peers; owned
 * buckets default sealed too). The passphrase is the ctor-injected secret,
 * default null, and null/'' is deliberately FORWARDED to put(), which
 * throws its own loud "sealed objects require a passphrase" — this slice
 * never invents a default secret.
 *
 * RULING (heartbeat on queue dispatch): a requested-queued run that put()
 * accepted (non-throwing) is a live pipeline — the heartbeat touches. A
 * queued run whose put returns ['queued'=>false] (sync queue ran it inline)
 * is treated as what the put result OBSERVED — inline upload, spool
 * unlinked, heartbeat from the object row. The report/ledger always mirror
 * the observed put result, never the request.
 *
 * RULING (spool on failure): the spool is always released with keepQueued
 * false on failure — a failed run owns no worker, so no tfbk-* file leaks
 * (design §11 sweep is wave 3).
 *
 * Provenance:
 * docs/superpowers/plans/2026-09-24-backup-store-remainder-design.md §2, §4,
 * §5, §9, §11 + verbatim docs/verbatim/2026-09-24-*.md:38-51 ("dump the
 * laravel database and make a backup from it in schedules to a channel").
 */
final class DatabaseBackupService
{
    /** Config-free bucket name when the ctor $bucket is '' (design §7: bucket 'default'). */
    private const DEFAULT_BUCKET = 'teleframe-backup:default';

    public function __construct(
        private readonly int $accountId,
        private readonly DatabaseDumper $dumper,
        private readonly StoreService $store,
        private readonly BackupRunIndexContract $runs,
        private readonly ?NoShowWatchdog $watchdog = null,
        private readonly ?\Closure $now = null,
        private readonly ?LoggerInterface $logger = null,
        private readonly ?string $passphrase = null,
        private readonly string $bucket = '',
        private readonly string $spoolDir = '',
    ) {}

    /**
     * Run ONE backup: dump $target, store it sealed in the run bucket,
     * record the ledger row, release the spool, and (only on success)
     * touch the 'backup' watchdog heartbeat.
     *
     * @throws InvalidArgumentException when $target is null — defaults are
     *                                  provider-passed only (see RULING in
     *                                  the class docblock)
     * @throws Throwable any failure — logged at error level
     *                   (when a logger is bound) and rethrown;
     *                   the watchdog is never touched
     */
    public function backup(?DatabaseTarget $target = null, bool $queued = false): BackupRunReport
    {
        if ($target === null) {
            throw new InvalidArgumentException(
                'backup() requires an explicit DatabaseTarget: config-free defaults are only reachable through the target the Laravel provider builds from teleframe.store.backup.database.*.'
            );
        }

        // Wave-1 vault-scope spool: the ctor-injected dir when the host
        // configured one, else DumpWorkspace's own config-free fallback. The
        // dumpers spool into it and release() owns the file lifecycle.
        //
        // NOTHING IS RESOLVED OR CREATED HERE beyond the string handoff: the
        // workspace only touches the filesystem on spool(), so an
        // unconfigured install still constructs, and a configured path that
        // cannot be made 0700 fails loud at the first dump rather than at
        // construction.
        $workspace = new DumpWorkspace($this->spoolDir);

        // 1. CLAIM THE RUN, BEFORE ANYTHING IS UPLOADED.
        //
        // WHY THE ORDER MOVED. It used to be dump → put → ledger, which meant a
        // run pointed at an account the ledger does not know failed its INSERT
        // *after* the dump had already been uploaded to the channel: an orphaned
        // remote copy with no local record and no way to find it. MEASURED
        // 2026-10-18, goal 46.87 item 4, from the host-install gate:
        //
        //   ✗ run-ledger-is-bound-to-the-account-registry
        //
        // The claim's name carries a marker, NOT the final object name, and that is
        // the correction to the first attempt at this fix. The ledger's key is
        // (account_id, object_name) and the dump decides its own object name from
        // its OWN clock — so a claim written under a guessed name and a final row
        // written under the dump's name are TWO rows, not one row updated.
        // MEASURED, by tests/Store/WorkerTickReachabilityTest.php:300 asserting
        // `actual size 2 matches expected size 1` — a gate that caught the mistake
        // this comment describes. The claim is therefore retired explicitly once
        // the real row lands.
        $startedAt = ($this->now ?? static fn (): int => time())();
        $claimName = $target->database.'-claim-'.gmdate('Y-m-d-His', $startedAt);

        try {
            $this->runs->upsertRun([
                'account_id' => $this->accountId,
                'bucket' => $this->bucket(),
                'driver' => $target->driver,
                'database' => $target->database,
                'object_name' => $claimName,
                'bytes' => 0,
                'chunks' => 0,
                'sha256' => '',
                'mode' => ObjectManifest::MODE_SEALED,
                'status' => 'running',
                'heartbeat_at_unix' => $startedAt,
            ]);
        } catch (QueryException $e) {
            // A RAW DRIVER ERROR IS NOT A HOST-FACING REFUSAL. An unconfigured or
            // mistyped account is an ordinary operator mistake, and `SQLSTATE[23000]
            // … FOREIGN KEY constraint failed` is not a sentence anyone can act on.
            // The dumper resolver already refuses by name for exactly this reason
            // ("no database driver configured — set …"); the claim refuses the same
            // way, and the original stays attached as the previous exception so the
            // driver detail is not lost, only demoted.
            throw new BackupException(sprintf(
                'cannot run a backup for account %d: the run ledger has no such account. '
                .'Register the account (or point the backup at one that exists) before running a backup — '
                .'nothing was dumped and nothing was uploaded.',
                $this->accountId,
            ), 0, $e);
        }

        try {
            // 2. dump → seekable spool file (design §3: tempfile, never a pipe).
            $dumpResult = $this->dumper->dump($target, $workspace);
            $objectName = $dumpResult->objectName();

            // 3. the EXISTING put path — sealed is the ONLY mode in the
            //    slice; the ctor passphrase is the secret (null/'' → put()
            //    throws its own loud sealed-empty error).
            $put = $this->store->put(
                $this->bucket(),
                $objectName,
                $dumpResult->path,
                ObjectManifest::MODE_SEALED,
                $this->passphrase,
                'application/sql',
                $queued,
            );

            // 4. the run-ledger row — identity from the dump, object truth
            //    observed from the put result (empty on queue dispatch).
            $report = BackupRunReport::fromPut($objectName, $put)
                ->withBucket($this->bucket())
                ->withDriver($dumpResult->driver)
                ->withDatabase($dumpResult->database);
            if ($report->queued) {
                // Not observable until the worker uploads; sealed is the
                // only mode in the slice, so record it (design §11).
                $report = $report->withMode(ObjectManifest::MODE_SEALED);
            } elseif ($report->heartbeatAt === null) {
                // Inline run whose put result carried no observable
                // timestamp (minimal fake / future seam): the injected
                // clock is the run's heartbeat (default time()).
                $report = $report->withHeartbeat(($this->now ?? static fn (): int => time())());
            }

            $this->runs->upsertRun([
                'account_id' => $this->accountId,
                'bucket' => $report->bucket,
                'driver' => $report->driver,
                'database' => $report->database,
                'object_name' => $report->name,
                'bytes' => $report->bytes,
                'chunks' => $report->chunks,
                'sha256' => $report->sha256,
                'mode' => $report->mode,
                'status' => $report->queued ? 'queued' : 'ok',
                'heartbeat_at_unix' => $report->heartbeatAt,
            ]);

            // 5. RETIRE THE CLAIM. The dump named its own object, so the real row
            //    has a different key and the claim would otherwise sit beside it
            //    forever as a phantom run that never completed.
            if ($report->name !== $claimName) {
                $this->runs->deleteRun($this->accountId, $claimName);
            }

            // 6. spool lifecycle: inline uploads (incl. a requested-queued
            //    run the sync queue uploaded inline) unlink; a genuinely
            //    dispatched run is kept for the worker (design §3/§4).
            $workspace->release($report->queued);

            // 7. heartbeat — SUCCESS only (design §4/§5): null watchdog =
            //    no-op; unconfigured-safe. A failed run never reaches here.
            $this->watchdog?->touch('backup');

            return $report;
        } catch (Throwable $e) {
            // Never leak the spool: a failed run owns no worker.
            $workspace->release(false);

            // The claim is already on disk, so a run that died after it is
            // DISCOVERABLE rather than invisible: it stays `running`, which is
            // exactly what it was when the process stopped. Recording `failed`
            // here would be a lie about a run that may have completed on a
            // worker this process never saw; the honest signal is a row that
            // never reached `ok`, and that is what a host reconciles against.
            $this->logger?->error('store backup: run failed', [
                'account_id' => $this->accountId,
                'driver' => $target->driver,
                'database' => $target->database,
                'queued' => $queued,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);
            throw $e;
        }
    }

    /**
     * The run ledger for this account, newest first. $bucket '' = all
     * buckets (contract listRuns null bucket). LIMIT >= 0 passes through
     * to the index (0 yields no rows; the default is 10).
     *
     * @return list<array<string, mixed>>
     */
    public function runs(string $bucket = '', int $limit = 10): array
    {
        return $this->runs->listRuns($this->accountId, $bucket === '' ? null : $bucket, $limit);
    }

    /** The run bucket: the provider-passed name, else the config-free default. */
    private function bucket(): string
    {
        return $this->bucket !== '' ? $this->bucket : self::DEFAULT_BUCKET;
    }
}
