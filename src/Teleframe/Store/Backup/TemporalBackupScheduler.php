<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Backup;

use MeRezaRezaei\Teleframe\Store\Schedules\SchedulePublisher;

/**
 * TemporalBackupScheduler (remainder design §8 wave 2: "Temporal
 * TemporalBackupScheduler install (dump + watchdog-check activities)") —
 * the thin, final schedule-install face of the store backup half.
 *
 * It adapts the BACKUP install payload onto the EXISTING SchedulePublisher
 * seam (Store/Schedules/*): a host binds a real TemporalSchedulePublisher
 * (agent D's parallel wave, docs/store.md:56 — wired to 127.0.0.1:7233,
 * namespace default) behind `teleclient.store.backup.schedules`, and this
 * scheduler hands it the BackupScheduleSpec payload verbatim.
 *
 * ADAPTER RULING (read the real surface, don't guess): the publisher seam
 * is `SchedulePublisher::install(array $schedules): array` — per-schedule
 * id => status, and each spec entry carries exactly `{interval_seconds,
 * activity}` (SchedulePublisher.php ctor contract: "Install or refresh the
 * configured schedules. Returns per-schedule status."). BackupScheduleSpec
 * ::installSpec() ALREADY emits that shape ({backup-dump,
 * backup-watchdog-check}, each with interval_seconds + activity), so
 * install() here performs NO payload reshaping — it maps the spec's two
 * schedules straight through the seam and returns the publisher's
 * per-schedule status array verbatim. A NullSchedulePublisher (the
 * default) records each schedule as 'skipped (null schedule driver)' —
 * the one-line-cron posture — without error.
 *
 * `$now` is deliberately ABSENT from the ctor (unlike the design §9
 * sketch, which drew a clock on the orchestrator): this thin adapter holds
 * NO timestamped state to inject a clock into — install() maps the spec
 * through the publisher verbatim and describes the publisher's posture. An
 * installed-at stamp is a wave-3 concern; it would arrive WITH its first
 * consumer, not before.
 *
 * Plain-PHP constructible, zero container calls (standalone-smoke-green).
 *
 * Provenance:
 * docs/superpowers/plans/2026-09-24-backup-store-remainder-design.md §5
 * (schedule default + publisher seam), §8 wave 2 ("Temporal
 * TemporalBackupScheduler install (dump + watchdog-check activities)"),
 * §9 (injected clock posture — see the absence ruling above) +
 * SchedulePublisher.php (the real seam signature this adapts).
 */
final class TemporalBackupScheduler
{
    public function __construct(
        private readonly SchedulePublisher $publisher,
        private readonly BackupScheduleSpec $spec,
    ) {}

    /**
     * Install (or refresh) the backup schedules through the bound
     * SchedulePublisher seam: {backup-dump, backup-watchdog-check}, each
     * carrying {interval_seconds, activity} from the spec.
     *
     * @return array<string, string> per-schedule status from the
     *                               publisher (e.g. 'skipped (null
     *                               schedule driver)' under the default)
     */
    public function install(): array
    {
        return $this->publisher->install($this->spec->installSpec());
    }

    /** The publisher's human-readable current state (CLI `schedule`). */
    public function describe(): string
    {
        return $this->publisher->describe();
    }
}
