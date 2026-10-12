<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store\Backup;

use MeRezaRezaei\Teleframe\Store\Backup\BackupScheduleSpec;
use PHPUnit\Framework\TestCase;

/**
 * BackupScheduleSpec (2026-09-24 backup-store remainder design §5/§9) —
 * the schedule payload builder: {backup-dump, backup-watchdog-check}
 * plus the watchdog expect-window and the human describe() posture.
 *
 * Windows (design §5, fresh-install grace): the watchdog CHECK cadence is
 * min(interval × 2, 3600) — a check always lands within the alert window,
 * never more often than hourly; the watchdog EXPECT window is
 * max(interval × 2, 3600) — one missed cycle + grace, floor 3600 s.
 *
 * describe() speaks the NullSchedulePublisher posture: schedules are off
 * by default, with the exact Temporal switch path + the one-line cron for
 * hosts without Temporal.
 *
 * Zero regex, pure data.
 */
final class BackupScheduleSpecTest extends TestCase
{
    public function test_install_spec_daily_default_exact_payload(): void
    {
        $spec = new BackupScheduleSpec(86400);

        self::assertSame([
            'backup-dump' => [
                'interval_seconds' => 86400,
                'activity' => 'teleframe:tstore backup',
            ],
            'backup-watchdog-check' => [
                // min(86400 × 2, 3600) — capped to hourly.
                'interval_seconds' => 3600,
                'activity' => 'teleclient.notify.check-no-show',
            ],
        ], $spec->installSpec());
    }

    public function test_install_spec_short_interval_checks_twice_as_often_within_the_hour(): void
    {
        // A 60 s dump cadence checks every 120 s (still < 3600, no cap).
        self::assertSame([
            'backup-dump' => [
                'interval_seconds' => 60,
                'activity' => 'teleframe:tstore backup',
            ],
            'backup-watchdog-check' => [
                'interval_seconds' => 120,
                'activity' => 'teleclient.notify.check-no-show',
            ],
        ], (new BackupScheduleSpec(60))->installSpec());
    }

    public function test_watchdog_interval_is_one_missed_cycle_plus_grace_floored_to_an_hour(): void
    {
        // Daily: 2 × 86400 — a full missed cycle + grace.
        self::assertSame(172800, (new BackupScheduleSpec(86400))->watchdogInterval());
        // Fresh/short installs never false-alarm before one hour.
        self::assertSame(3600, (new BackupScheduleSpec(60))->watchdogInterval());
        self::assertSame(3600, (new BackupScheduleSpec(1))->watchdogInterval());
    }

    public function test_describe_mentions_null_publisher_and_temporal_switch_path(): void
    {
        $describe = (new BackupScheduleSpec(86400))->describe();

        self::assertStringContainsString('Schedule driver: null', $describe);
        self::assertStringContainsString('Store backup schedules are off', $describe);
        self::assertStringContainsString('teleclient.store.backup.schedules=temporal', $describe);
        self::assertStringContainsString('Temporal at 127.0.0.1:7233', $describe);
        self::assertStringContainsString('teleframe:tstore backup', $describe);
        self::assertStringContainsString('0 0 * * * cd $APP && php artisan teleframe:tstore backup', $describe);
    }

    public function test_describe_uses_the_configured_temporal_dsn_when_present(): void
    {
        $describe = (new BackupScheduleSpec(86400, 'temporal.internal:8233'))->describe();

        self::assertStringContainsString('Temporal at temporal.internal:8233', $describe);
    }
}
