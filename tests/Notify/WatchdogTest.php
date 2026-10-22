<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Notify;

use InvalidArgumentException;
use MeRezaRezaei\Teleframe\Handler\InMemoryCache;
use MeRezaRezaei\Teleframe\Notify\Level;
use MeRezaRezaei\Teleframe\Notify\NoShowWatchdog;
use MeRezaRezaei\Teleframe\Notify\WatchAlert;
use MeRezaRezaei\Teleframe\Notify\WatchState;
use PHPUnit\Framework\TestCase;

/**
 * NoShowWatchdog (design §2/G10, wave 2): the register/touch/check lifecycle
 * against the real PSR-16 cache seam (InMemoryCache in
 * src/Teleframe/Handler/InMemoryCache.php) under an injected mutable clock.
 * Covers the in-window no-alert case, the overdue WatchAlert on
 * {topic}.missing at alert level, the injected notify-queue seam, clear()
 * re-arming the grace period, and the optional probe source preference.
 */
final class WatchdogTest extends TestCase
{
    public function test_register_touch_check_returns_empty_before_the_window(): void
    {
        $now = 1_000;
        $clock = static function () use (&$now): int {
            return $now;
        };
        $state = new WatchState(new InMemoryCache);
        $watchdog = new NoShowWatchdog($state, null, $clock);

        $watchdog->register('backup', 60);
        $watchdog->touch('backup'); // heartbeat recorded at now = 1000

        self::assertSame(1_000, $state->lastSeen('backup'));
        self::assertSame(1_000, $watchdog->lastSeen('backup'));

        $now = 1_060; // age 60 == expected window -> not (yet) overdue
        self::assertSame([], $watchdog->check());

        $now = 1_061; // age 61 > 60 -> overdue
        self::assertCount(1, $watchdog->check());
    }

    public function test_overdue_raises_watch_alert_on_topic_missing_at_alert_level(): void
    {
        $now = 1_000;
        $clock = static function () use (&$now): int {
            return $now;
        };
        $watchdog = new NoShowWatchdog(new WatchState(new InMemoryCache), null, $clock);
        $watchdog->register('backup', 60);
        $watchdog->touch('backup');

        $now = 2_000;
        $alerts = $watchdog->check();

        self::assertCount(1, $alerts);
        $alert = $alerts[0];
        self::assertInstanceOf(WatchAlert::class, $alert);
        self::assertSame('backup', $alert->topic);
        self::assertSame(60, $alert->expectedEvery);
        self::assertSame(1_000, $alert->lastSeen);
        self::assertSame(1_000, $alert->age);

        $notify = $alert->toNotify();
        self::assertSame('backup.missing', $notify['topic']);
        self::assertSame(Level::Alert->value, $notify['level']);
        self::assertStringContainsString('no update arrived for backup', $notify['brief']);
        self::assertSame('backup', $notify['context']['topic']);
        self::assertSame(60, $notify['context']['expected_every_seconds']);
        self::assertSame(1_000, $notify['context']['age_seconds']);

        $row = $alert->toRow();
        self::assertSame('backup', $row['topic']);
        self::assertSame(60, $row['expected_every_s']);
        self::assertSame(1_000, $row['age_s']);
    }

    public function test_overdue_alert_lands_in_the_injected_notify_queue(): void
    {
        $now = 1_000;
        $clock = static function () use (&$now): int {
            return $now;
        };
        $queue = new RecordingQueue;
        $watchdog = new NoShowWatchdog(new WatchState(new InMemoryCache), $queue, $clock);
        $watchdog->register('backup', 60);
        $watchdog->touch('backup');

        $now = 2_000;
        self::assertCount(1, $watchdog->check());

        self::assertCount(1, $queue->jobs);
        $job = $queue->jobs[0];
        self::assertSame('backup.missing', $job->topic);
        self::assertSame('alert', $job->level);
        self::assertFalse($job->premium);
        self::assertSame(0, $job->attempts);
        self::assertSame('backup', $job->context['topic']);
        self::assertSame(60, $job->context['expected_every_seconds']);
        self::assertSame(1_000, $job->context['last_seen']);
        self::assertSame(1_000, $job->context['age_seconds']);
    }

    public function test_no_last_seen_yet_is_grace_not_a_false_alarm(): void
    {
        $now = 1_000;
        $clock = static function () use (&$now): int {
            return $now;
        };
        $watchdog = new NoShowWatchdog(new WatchState(new InMemoryCache), null, $clock);
        $watchdog->register('backup', 60);

        $now = 2_000; // never touched: a fresh registration must not false-alarm
        self::assertSame([], $watchdog->check());
    }

    public function test_clear_forgets_last_seen_and_rearms_the_grace_period(): void
    {
        $now = 1_000;
        $clock = static function () use (&$now): int {
            return $now;
        };
        $state = new WatchState(new InMemoryCache);
        $watchdog = new NoShowWatchdog($state, null, $clock);
        $watchdog->register('backup', 60);
        $watchdog->touch('backup');
        self::assertSame(1_000, $state->lastSeen('backup'));

        $state->clear('backup');
        self::assertNull($state->lastSeen('backup'));
        self::assertNull($watchdog->lastSeen('backup'));

        $now = 2_000;
        self::assertSame([], $watchdog->check());
    }

    public function test_probe_is_preferred_over_state_last_seen(): void
    {
        $now = 1_000;
        $clock = static function () use (&$now): int {
            return $now;
        };
        $state = new WatchState(new InMemoryCache);
        $watchdog = new NoShowWatchdog($state, null, $clock);

        // The state says the heartbeat was 1000s ago; the probe says it was
        // seen just now -> no alert while the alternative source answers.
        $watchdog->register('backup', 60, static fn (string $topic): int => 1_999);
        $watchdog->touch('backup');

        $now = 2_000;
        self::assertSame([], $watchdog->check());

        // The probe drops out (null) -> the state's stale last-seen governs.
        $watchdog->register('backup', 60, static fn (string $topic) => null);
        self::assertCount(1, $watchdog->check());
    }

    public function test_registrations_track_the_registered_rows(): void
    {
        $clock = static fn (): int => 1_000;
        $watchdog = new NoShowWatchdog(new WatchState(new InMemoryCache), null, $clock);
        $watchdog->register('backup', 3_600);
        $watchdog->register('heartbeat', 300);

        self::assertTrue($watchdog->isRegistered('backup'));
        self::assertFalse($watchdog->isRegistered('nope'));

        $rows = $watchdog->registrations();
        self::assertSame(['backup', 'heartbeat'], array_keys($rows));
        self::assertSame(3_600, $rows['backup']['expected_every']);
        self::assertNull($rows['backup']['probe']);
    }

    public function test_register_rejects_empty_topics_and_non_positive_intervals(): void
    {
        $watchdog = new NoShowWatchdog(new WatchState(new InMemoryCache));

        try {
            $watchdog->register('', 60);
            self::fail('an empty topic must be rejected');
        } catch (InvalidArgumentException $e) {
            self::assertStringContainsString('must not be empty', $e->getMessage());
        }

        try {
            $watchdog->register('backup', 0);
            self::fail('a non-positive interval must be rejected');
        } catch (InvalidArgumentException $e) {
            self::assertStringContainsString('must be > 0', $e->getMessage());
        }
    }
}
