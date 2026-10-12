<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Notify;

use MeRezaRezaei\Teleframe\Handler\InMemoryCache;
use MeRezaRezaei\Teleframe\Notify\NoShowWatchdog;
use MeRezaRezaei\Teleframe\Notify\NotificationJob;
use MeRezaRezaei\Teleframe\Notify\WatchState;
use PHPUnit\Framework\TestCase;

/**
 * The PROCESS-BOUNDARY proof for the backup no-show watchdog
 * (StoreBackupServiceProvider binds the shared watchdog; the heartbeat
 * is recorded by a backup run in one process, the check runs in another).
 *
 * Why the shape of these tests matters: WatchdogTest registers and checks
 * on the SAME in-memory object, which proves nothing about a registration
 * that has to survive a process boundary. Every test here therefore
 * builds a SECOND, DISTINCT watchdog — one that never saw register() —
 * and asserts the no-show still fires.
 */
final class BackupWatchdogProcessBoundaryTest extends TestCase
{
    /** The durable registration the provider derives from the backup schedule spec. */
    private function backupSource(bool $enabled = true, int $expectEvery = 60): callable
    {
        return static function () use ($enabled, $expectEvery): array {
            return $enabled ? ['backup' => $expectEvery] : [];
        };
    }

    public function test_a_stale_heartbeat_alerts_in_a_process_that_never_registered_anything(): void
    {
        $cache = new InMemoryCache;
        $now = 1_000;

        // Process A: the process that ran a backup and recorded the heartbeat.
        $producer = new NoShowWatchdog(
            state: new WatchState($cache),
            now: static function () use (&$now): int {
                return $now;
            },
        );
        $producer->touch('backup');

        // Process B: a COMPLETELY separate construction — it has an empty
        // in-memory registration map and only the durable source.
        $watchdog = new NoShowWatchdog(
            state: new WatchState($cache),
            now: static function () use (&$now): int {
                return $now;
            },
            registrationSource: $this->backupSource(),
        );

        self::assertNotSame($producer, $watchdog);
        self::assertSame([], $producer->registrations(), 'the producer process registered nothing');
        self::assertArrayHasKey('backup', $watchdog->registrations(), 'the checker sees the durable registration');

        $now = 2_000; // age 1000 > 60
        $alerts = $watchdog->check();

        self::assertCount(1, $alerts);
        self::assertSame('backup', $alerts[0]->topic);
        self::assertSame(1_000, $alerts[0]->age);
    }

    public function test_the_alert_reaches_the_notify_queue_from_the_second_process(): void
    {
        $cache = new InMemoryCache;
        $now = 1_000;

        // A backup run in another process records the heartbeat; the touch
        // needs no registration at all (design §8).
        (new NoShowWatchdog(
            state: new WatchState($cache),
            now: static function () use (&$now): int {
                return $now;
            },
        ))->touch('backup');

        $queue = new RecordingQueue;
        $watchdog = new NoShowWatchdog(
            state: new WatchState($cache),
            notify: $queue,
            now: static function () use (&$now): int {
                return $now;
            },
            registrationSource: $this->backupSource(),
        );

        $now = 2_000;
        $watchdog->check();

        self::assertCount(1, $queue->jobs);
        self::assertSame('backup.missing', $queue->jobs[0]->topic);
        self::assertSame('alert', $queue->jobs[0]->level);
    }

    public function test_a_fresh_heartbeat_produces_no_alert_in_the_checking_process(): void
    {
        $cache = new InMemoryCache;
        $now = 1_000;

        (new NoShowWatchdog(
            state: new WatchState($cache),
            now: static function () use (&$now): int {
                return $now;
            },
        ))->touch('backup');

        $queue = new RecordingQueue;
        $watchdog = new NoShowWatchdog(
            state: new WatchState($cache),
            notify: $queue,
            now: static function () use (&$now): int {
                return $now;
            },
            registrationSource: $this->backupSource(),
        );

        $now = 1_030; // age 30 <= 60 -> inside the window
        self::assertSame([], $watchdog->check());
        self::assertSame([], $queue->jobs);
    }

    public function test_a_disabled_schedule_source_watches_nothing(): void
    {
        $cache = new InMemoryCache;
        $now = 1_000;
        (new NoShowWatchdog(
            state: new WatchState($cache),
            now: static function () use (&$now): int {
                return $now;
            },
        ))->touch('backup');

        $watchdog = new NoShowWatchdog(
            state: new WatchState($cache),
            now: static function () use (&$now): int {
                return $now;
            },
            registrationSource: $this->backupSource(enabled: false),
        );

        $now = 9_999;
        self::assertSame([], $watchdog->registrations());
        self::assertSame([], $watchdog->check());
    }

    public function test_an_in_memory_registration_wins_over_the_durable_source_for_the_same_topic(): void
    {
        $cache = new InMemoryCache;
        $now = 1_000;
        (new NoShowWatchdog(
            state: new WatchState($cache),
            now: static function () use (&$now): int {
                return $now;
            },
        ))->touch('backup');

        $watchdog = new NoShowWatchdog(
            state: new WatchState($cache),
            now: static function () use (&$now): int {
                return $now;
            },
            registrationSource: $this->backupSource(expectEvery: 60),
        );
        $watchdog->register('backup', 3_600); // a host override widens the window

        self::assertSame(3_600, $watchdog->registrations()['backup']['expected_every']);

        $now = 1_100; // overdue against 60, fine against 3600
        self::assertSame([], $watchdog->check());

        $now = 4_700;
        self::assertCount(1, $watchdog->check());
    }

    public function test_the_durable_source_is_never_misconfigured_silently(): void
    {
        $watchdog = new NoShowWatchdog(
            state: new WatchState(new InMemoryCache),
            registrationSource: static fn (): array => ['backup' => 0],
        );

        $this->expectException(\InvalidArgumentException::class);
        $watchdog->registrations();
    }

    public function test_is_registered_answers_for_a_durably_derived_topic(): void
    {
        $watchdog = new NoShowWatchdog(
            state: new WatchState(new InMemoryCache),
            registrationSource: $this->backupSource(),
        );

        self::assertTrue($watchdog->isRegistered('backup'));
        self::assertFalse($watchdog->isRegistered('heartbeat'));
    }

    public function test_the_alert_job_carries_no_session_shaped_material(): void
    {
        $cache = new InMemoryCache;
        $now = 1_000;
        (new NoShowWatchdog(
            state: new WatchState($cache),
            now: static function () use (&$now): int {
                return $now;
            },
        ))->touch('backup');

        $queue = new RecordingQueue;
        $watchdog = new NoShowWatchdog(
            state: new WatchState($cache),
            notify: $queue,
            now: static function () use (&$now): int {
                return $now;
            },
            registrationSource: $this->backupSource(),
        );

        $now = 2_000;
        $watchdog->check();

        $job = $queue->jobs[0] ?? null;
        self::assertInstanceOf(NotificationJob::class, $job);
        self::assertSame(['topic', 'expected_every_seconds', 'last_seen', 'age_seconds'], array_keys($job->context));
    }
}
