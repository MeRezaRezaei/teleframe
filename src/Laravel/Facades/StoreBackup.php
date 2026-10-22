<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use MeRezaRezaei\Teleframe\Store\Backup\BackupRunReport;
use MeRezaRezaei\Teleframe\Store\Backup\BackupScheduleSpec;
use MeRezaRezaei\Teleframe\Store\Backup\BackupSupport;
use MeRezaRezaei\Teleframe\Store\Backup\DatabaseBackupService;
use MeRezaRezaei\Teleframe\Store\Backup\DatabaseTarget;

/**
 * StoreBackup facade (design 2026-09-24 §7): the DB-dump half of TStore —
 * dump → sealed put into the `teleframe-backup:` bucket → run ledger →
 * heartbeat. Routes through DatabaseBackupService bound under
 * BackupSupport::SERVICE_KEY (a rebindable container seam, default built
 * from teleframe.store.backup config by StoreBackupServiceProvider).
 *
 *     StoreBackup::backup();                        // one run, default target
 *     StoreBackup::backup($target, true);           // async-queue upload
 *     StoreBackup::runs('teleframe-backup:default');// ledger rows
 *     StoreBackup::schedule()->describe();          // install payload
 *     StoreBackup::service();
 *
 * `backup()` and `schedule()` are REAL static methods — the core never
 * fabricates a DatabaseTarget config-free (backup(null) throws, B3 RULING)
 * and has no schedule() accessor — so the facade resolves the
 * provider-built default target (DatabaseTarget::class) and builds the
 * BackupScheduleSpec from teleframe.store.backup.schedule.* itself.
 * `runs()`/`service()` forward to the service root.
 *
 * Unconfigured (no teleframe.store.backup.database.driver): the service
 * still resolves — backup() throws loud on first dump use, never
 * silent-drop (TStore posture).
 *
 * @method static array runs(string $bucket = '', int $limit = 10)
 *
 * @see BackupSupport
 */
class StoreBackup extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return BackupSupport::SERVICE_KEY;
    }

    /**
     * The resolved DatabaseBackupService root — the same instance the
     * static backup()/runs() calls route through (explicit forwarding,
     * not __callStatic: the core orchestrator has no service() accessor).
     */
    public static function service(): DatabaseBackupService
    {
        return static::getFacadeRoot();
    }

    /**
     * ONE backup run. With no explicit target, the config-built default
     * (teleframe.store.backup.database.*) is resolved and handed in — same
     * target the `teleframe:tstore backup` CLI uses.
     */
    public static function backup(?DatabaseTarget $target = null, bool $queued = false): BackupRunReport
    {
        if ($target === null) {
            $target = static::getFacadeApplication()->make(DatabaseTarget::class);
        }

        return static::getFacadeRoot()->backup($target, $queued);
    }

    /**
     * The BackupScheduleSpec install payload for the configured interval
     * (teleframe.store.backup.schedule.interval_seconds, default 86400).
     */
    public static function schedule(): BackupScheduleSpec
    {
        $interval = (int) (static::getFacadeApplication()->make('config')->get('teleframe.store.backup.schedule.interval_seconds') ?? 86400);

        return new BackupScheduleSpec($interval);
    }
}
