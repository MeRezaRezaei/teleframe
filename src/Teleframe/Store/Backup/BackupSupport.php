<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Backup;

/**
 * BackupSupport (remainder design §2/§7) — the plain-PHP bind-key anchor
 * for the store backup half, mirroring the Notify module's FacadeSupport
 * precedent: the fixed `teleclient.store.backup.*` container seams live
 * here as constants so the provider, the command, the facade and any host
 * wiring agree on the exact keys without string literals scattered
 * through the tree.
 *
 * The seven seams (five wave-1 + two wave-2-D):
 *   SERVICE_KEY   → DatabaseBackupService (the orchestrator)
 *   DUMPER_KEY    → DatabaseDumper (MysqlDumper|PostgresDumper; the
 *                   unconfigured driver '' binds a loud-throwing stub)
 *   INDEX_KEY     → BackupRunIndexContract (Eloquent | Memory ledger)
 *   WATCHDOG_KEY  → ?NoShowWatchdog (null-safe heartbeat seam)
 *   SCHEDULES_KEY → SchedulePublisher (NullSchedulePublisher default)
 *   TARGETS_KEY   → ?PeerTargetRegistry (wave-2-D: config target rows;
 *                   null until the wave-2-A sibling class ships)
 *   SCHEDULER_KEY → TemporalBackupScheduler (thin adapter over the
 *                   SchedulePublisher seam; the host's Temporal install
 *                   face for {backup-dump, backup-watchdog-check})
 *
 * Pure data — one constant per key, zero behaviour. Plain-PHP
 * constructible by definition (no ctor).
 *
 * Provenance:
 * docs/superpowers/plans/2026-09-24-backup-store-remainder-design.md §2
 * (BackupSupport row), §7 (bind keys block, lines 290-298).
 */
final class BackupSupport
{
    /** The DatabaseBackupService orchestrator seam (facade accessor). */
    public const SERVICE_KEY = 'teleclient.store.backup.service';

    /** The DatabaseDumper seam (driver picked from config at bind time). */
    public const DUMPER_KEY = 'teleclient.store.backup.dumper';

    /** The BackupRunIndexContract seam (Eloquent | Memory ledger). */
    public const INDEX_KEY = 'teleclient.store.backup.index';

    /** The ?NoShowWatchdog heartbeat seam (null-safe, unconfigured = no-op). */
    public const WATCHDOG_KEY = 'teleclient.store.backup.watchdog';

    /** The SchedulePublisher seam (NullSchedulePublisher default; Temporal wave 2). */
    public const SCHEDULES_KEY = 'teleclient.store.backup.schedules';

    /** The ?PeerTargetRegistry seam (wave-2-D: built from teleframe.store.backup.targets; null until the sibling class ships). */
    public const TARGETS_KEY = 'teleclient.store.backup.targets';

    /** The TemporalBackupScheduler seam (wave-2-D: thin adapter over the SchedulePublisher seam). */
    public const SCHEDULER_KEY = 'teleclient.store.backup.scheduler';
}
