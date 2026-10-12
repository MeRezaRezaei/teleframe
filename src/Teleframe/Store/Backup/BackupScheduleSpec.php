<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Backup;

/**
 * BackupScheduleSpec (remainder design §5/§9) — builds the
 * SchedulePublisher->install() payload for the store backup half:
 * {backup-dump, backup-watchdog-check}, plus the human describe() and
 * the watchdog expect-window. Pure data, no container, plain-PHP
 * constructible (standalone-smoke-green).
 *
 * Posture: schedules are a convergence convenience, not a correctness
 * gate — the default driver is null (NullSchedulePublisher) and describe()
 * explains how to switch on Temporal; hosts without Temporal get the
 * documented one-line cron (`0 0 * * * cd $APP && php artisan
 * teleframe:tstore backup`). installSpec() is safe to hand to a null
 * publisher (it records 'skipped' per schedule) and safe to call
 * unconfigured — config, not construction, decides whether schedules run.
 *
 * Windows: the dump interval is the config interval verbatim; the
 * watchdog-check cadence is the notice window halved-and-capped so a
 * check always lands within the alert window for long intervals while
 * never polling faster than hourly; watchdogInterval() is the expect
 * window itself — one missed cycle + grace, floor 3600 s (fresh-install
 * grace, design §5 / spec §6 Q5).
 *
 * Provenance:
 * docs/superpowers/plans/2026-09-24-backup-store-remainder-design.md §5
 * (schedule default + heartbeat wiring, lines 197-219), §9 (ctor) +
 * verbatim docs/verbatim/2026-09-24-*.md:38-51 ("dump the laravel
 * database ... in schedules").
 */
final class BackupScheduleSpec
{
    /**
     * @param  int  $intervalSeconds  dump cadence in seconds (86400 = daily default)
     * @param  string  $publisherDsn  Temporal frontend address; '' = not configured
     */
    public function __construct(
        private readonly int $intervalSeconds,
        private readonly string $publisherDsn = '',
    ) {}

    /**
     * The SchedulePublisher->install() payload: {backup-dump, backup-watchdog-check}.
     *
     * @return array<string, array{interval_seconds: int, activity: string}>
     */
    public function installSpec(): array
    {
        return [
            'backup-dump' => [
                'interval_seconds' => $this->intervalSeconds,
                'activity' => 'teleframe:tstore backup',
            ],
            'backup-watchdog-check' => [
                // Check at least hourly; for long dump intervals the check
                // still lands well inside the alert window.
                'interval_seconds' => min($this->intervalSeconds * 2, 3600),
                'activity' => 'teleclient.notify.check-no-show',
            ],
        ];
    }

    /**
     * The watchdog expect-window for the 'backup' topic: one missed cycle
     * + grace, floor 3600 s so a fresh install never false-alarms.
     */
    public function watchdogInterval(): int
    {
        return max($this->intervalSeconds * 2, 3600);
    }

    /**
     * Human posture, NullSchedulePublisher tone: off by default, with the
     * exact switch path. The one-line cron stays available for hosts
     * without Temporal.
     */
    public function describe(): string
    {
        $dsn = $this->publisherDsn !== '' ? $this->publisherDsn : '127.0.0.1:7233';

        return "Schedule driver: null. Store backup schedules are off.\n"
            .'Set teleclient.store.backup.schedules=temporal (Temporal at '.$dsn
            .", namespace default) to enable the daily dump + watchdog no-show check.\n"
            .'No Temporal? Run the cron line: 0 0 * * * cd $APP && php artisan teleframe:tstore backup';
    }
}
