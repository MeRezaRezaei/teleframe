<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Notify;

use MeRezaRezaei\Teleframe\Laravel\Facades\Notify;

/**
 * FacadeSupport — the plain-PHP bind-key anchor for the Notify module
 * (design §2/G9). Holds the fixed `teleclient.*` container seams so the
 * Laravel facade, the provider and the CLI command all resolve the same
 * keys without the core module depending on an artisan Command class.
 *
 * Keys are opaque wire names (AGENTS.md: fixed `teleclient.*` convention,
 * never renamed): the service key is the facade accessor, the queue key
 * is the NotificationQueueContract seam, the schedules key is the
 * SchedulePublisher-shaped seam (null default — see the provider).
 *
 * @see Notify
 */
final class FacadeSupport
{
    /** Container seam: NotifyService (built by NotifyServiceProvider). */
    public const SERVICE_KEY = 'teleclient.notify.service';

    /** Container seam: NotificationQueueContract (sync default). */
    public const QUEUE_KEY = 'teleclient.notify.queue';

    /** Container seam: SchedulePublisher (null default, Temporal later). */
    public const SCHEDULES_KEY = 'teleclient.notify.schedules';

    /**
     * Container seam: the SHARED NoShowWatchdog singleton. Producers (the
     * store backup run that records the heartbeat) and the check (the CLI,
     * the scheduler, a host cron) must meet on ONE object over ONE PSR-16
     * store — two watchdogs are two different notions of "what is watched".
     *
     * Bound by whichever module owns a watch (the store backup module binds
     * the backup no-show); unbound when no module watches anything, and the
     * CLI then builds its own local watchdog (same construction, same
     * durable state, no host obligation).
     */
    public const WATCHDOG_KEY = 'teleclient.notify.watchdog';

    /**
     * Container seam: `Psr3NotifyBridge` — the PSR-3 `LoggerInterface`
     * adapter that turns ordinary log records into notify sends, which is
     * what makes this a logger system rather than a notifier that can log.
     *
     * DELIBERATELY NOT `Psr\Log\LoggerInterface`: binding that key here
     * would hijack the host's whole log channel and close a loop — notify's
     * OWN internal warnings are logged through LoggerInterface, so a bridge
     * bound as LoggerInterface would feed them back into a send, forever.
     * A host opts in by naming this key (or the class name) explicitly:
     *
     *     Log::channel(...)  /  config('logging.channels.telegram')
     *         -> app(MeRezaRezaei\Teleframe\Notify\FacadeSupport::PSR3_KEY)
     *
     * @see Psr3NotifyBridge
     */
    public const PSR3_KEY = 'teleclient.notify.psr3';

    /**
     * Container seam: `NotificationLogger` — the typed `Level`→topic log
     * funnel (registry row first, `Level::defaultTopic()` as the fallback)
     * that returns the notified job id. The PSR-3 half above is for a host
     * that logs with PSR-3 words; this one is for a host that logs with
     * `Level` cases and wants the job id back.
     *
     * @see NotificationLogger
     */
    public const LEVEL_LOGGER_KEY = 'teleclient.notify.level_logger';
}
