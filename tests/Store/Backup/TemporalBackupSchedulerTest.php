<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store\Backup;

use MeRezaRezaei\Teleframe\Store\Backup\BackupScheduleSpec;
use MeRezaRezaei\Teleframe\Store\Backup\TemporalBackupScheduler;
use MeRezaRezaei\Teleframe\Store\Schedules\SchedulePublisher;
use PHPUnit\Framework\TestCase;

/**
 * TemporalBackupScheduler (2026-09-24 backup-store remainder design §5/§8,
 * cycle 11 wave 2) — the thin schedule-install face: adapts the
 * BackupScheduleSpec install payload onto the EXISTING SchedulePublisher
 * seam with NO payload reshaping.
 *
 * Coverage, against a recording fake SchedulePublisher (anonymous class):
 * install() hands the publisher EXACTLY the spec's
 * BackupScheduleSpec::installSpec() shape {backup-dump,
 * backup-watchdog-check} each carrying {interval_seconds, activity}, and
 * returns the publisher's per-schedule status VERBATIM; describe()
 * delegates to the publisher's describe().
 *
 * Zero container, plain-PHP.
 */
final class TemporalBackupSchedulerTest extends TestCase
{
    public function test_install_passes_the_spec_payload_through_and_returns_status_verbatim(): void
    {
        $publisher = new class implements SchedulePublisher
        {
            /** @var array<string, mixed>|null */
            public ?array $installed = null;

            public function install(array $schedules): array
            {
                $this->installed = $schedules;

                return [
                    'backup-dump' => 'installed',
                    'backup-watchdog-check' => 'installed',
                ];
            }

            public function describe(): string
            {
                return 'fake publisher: nothing to describe';
            }
        };

        $spec = new BackupScheduleSpec(86400);
        $scheduler = new TemporalBackupScheduler($publisher, $spec);

        $status = $scheduler->install();

        // The payload shape is the spec's, straight through, unchanged.
        self::assertSame($spec->installSpec(), $publisher->installed);
        self::assertSame([
            'backup-dump' => [
                'interval_seconds' => 86400,
                'activity' => 'teleframe:tstore backup',
            ],
            'backup-watchdog-check' => [
                'interval_seconds' => 3600,
                'activity' => 'teleclient.notify.check-no-show',
            ],
        ], $publisher->installed);

        // The publisher's status array is returned verbatim — the adapter
        // performs no reshaping on the way back out.
        self::assertSame([
            'backup-dump' => 'installed',
            'backup-watchdog-check' => 'installed',
        ], $status);
    }

    public function test_install_uses_the_spec_interval_verbatim(): void
    {
        $publisher = new class implements SchedulePublisher
        {
            public ?array $installed = null;

            public function install(array $schedules): array
            {
                $this->installed = $schedules;

                return $schedules;
            }

            public function describe(): string
            {
                return 'fake';
            }
        };

        $status = (new TemporalBackupScheduler($publisher, new BackupScheduleSpec(60)))->install();

        self::assertSame(60, $publisher->installed['backup-dump']['interval_seconds']);
        self::assertSame(120, $publisher->installed['backup-watchdog-check']['interval_seconds']);
        self::assertSame($publisher->installed, $status);
    }

    public function test_describe_delegates_to_the_publisher(): void
    {
        $publisher = new class implements SchedulePublisher
        {
            public function install(array $schedules): array
            {
                return [];
            }

            public function describe(): string
            {
                return 'temporal at 127.0.0.1:7233 — live';
            }
        };

        self::assertSame(
            'temporal at 127.0.0.1:7233 — live',
            (new TemporalBackupScheduler($publisher, new BackupScheduleSpec(86400)))->describe(),
        );
    }
}
