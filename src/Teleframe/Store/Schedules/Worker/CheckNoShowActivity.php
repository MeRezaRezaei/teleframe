<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Schedules\Worker;

use MeRezaRezaei\Teleframe\Notify\NoShowWatchdog;
use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;
use Temporal\Activity\ActivityOptions;
use Temporal\Common\RetryOptions;

/**
 * CheckNoShowActivity — activity type `teleframe.notify.check-no-show`, the
 * work behind BOTH no-show schedules (`backup-watchdog-check` from
 * BackupScheduleSpec and `teleframe.notify.watchdog` from NotifyCommand).
 *
 * The real work is `NoShowWatchdog::check()` — the same call
 * `php artisan teleframe:notify --check` makes. It returns the list of
 * WatchAlerts whose topics have gone quiet past their expected window, and
 * emits each through the notify queue.
 *
 * WatchAlert is serialised through its own `toNotify()` shape rather than
 * reflected, so the activity's return value is plain data an activity payload
 * can carry.
 *
 * Plain-PHP constructible, no container, zero regex.
 */
#[ActivityInterface]
final class CheckNoShowActivity
{
    /** The activity type registered on the worker and stubbed by the workflow. */
    public const TYPE = 'teleframe.notify.check-no-show';

    /**
     * The shared watchdog seam. `FacadeSupport::WATCHDOG_KEY` is the alias
     * both NotifyServiceProvider and StoreBackupServiceProvider bind, so one
     * name reaches either module's watchdog.
     */
    public const WATCHDOG_SEAM = 'teleclient.notify.watchdog';

    public function __construct(
        private readonly ContainerResolver $resolver = new ContainerResolver,
    ) {}

    public static function options(): ActivityOptions
    {
        return ActivityOptions::new()
            // A check is a cache read plus a compare: seconds, not minutes.
            ->withStartToCloseTimeout(60)
            ->withRetryOptions(
                RetryOptions::new()
                    ->withMaximumAttempts(3),
            );
    }

    /**
     * Check every registered topic and return the alerts that fired.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    #[ActivityMethod(name: self::TYPE)]
    public function check(array $payload): array
    {
        $watchdog = $this->resolver->resolve(self::WATCHDOG_SEAM);

        $watchdog instanceof NoShowWatchdog or throw new \RuntimeException(sprintf(
            'The "%s" seam resolved to %s, which is not a %s. Without it this check has nothing to compare '
            .'against, and reporting "no alerts" for a watchdog that does not exist is a false all-clear.',
            self::WATCHDOG_SEAM,
            $watchdog::class,
            NoShowWatchdog::class,
        ));

        $alerts = [];

        foreach ($watchdog->check() as $alert) {
            $alerts[] = [
                'topic' => $alert->topic,
                'expected_every' => $alert->expectedEvery,
                'last_seen' => $alert->lastSeen,
                'age' => $alert->age,
            ];
        }

        return [
            'checked' => count($watchdog->registrations()),
            'alerts' => $alerts,
            'alert_count' => count($alerts),
        ];
    }
}
