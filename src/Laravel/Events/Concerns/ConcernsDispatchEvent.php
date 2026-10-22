<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Events\Concerns;

use Illuminate\Support\Facades\Event;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

/**
 * Dispatch a Telegram event without ever breaking the wire, and without
 * letting a host's failing listener disappear.
 *
 * The swallow is the older half and it is CORRECT: these events are raised
 * from inside the MTProto read loop and from the webhook controller, where a
 * throwing host listener would otherwise stop the connection or turn a
 * successful webhook into a non-200 — and Telegram RETRIES a non-200, so one
 * broken listener would deliver the same update forever.
 *
 * What was missing is the other half. Every one of these four dispatchers had
 * an empty `catch (\Throwable) {}`, so a host whose listener threw got no
 * exception, no log line, and no signal of any kind — the update simply
 * vanished. That is the same "reports health while hiding a failure" shape
 * fixed elsewhere in this release, and it is the one that costs a host the
 * most time, because a handler that stopped running looks exactly like a
 * Telegram account that went quiet.
 *
 * So: keep the swallow, add the log. The package's silence guarantee is
 * untouched — an unconfigured host binds NullLogger and still gets nothing —
 * but a host that HAS a logger now learns that its own listener failed, by
 * name, with the throwable attached.
 */
trait ConcernsDispatchEvent
{
    /**
     * Dispatch, swallowing a listener failure but never hiding it.
     *
     * @param  object  $event  the event instance to dispatch
     */
    private static function dispatchTelegramEvent(object $event): void
    {
        if (! class_exists(Event::class) || ! Event::getFacadeApplication()) {
            return;
        }

        try {
            Event::dispatch($event);
        } catch (Throwable $failure) {
            self::reportListenerFailure($event::class, $failure);
        }
    }

    /**
     * Best-effort PSR-3 report. Named so the host can grep one string.
     *
     * The resolution is deliberately defensive: this runs inside the catch of
     * a method whose entire contract is that it does not throw, so a container
     * that cannot produce a logger must not convert a swallowed listener
     * failure into a thrown one.
     */
    private static function reportListenerFailure(string $event, Throwable $failure): void
    {
        try {
            $app = Event::getFacadeApplication();

            $logger = $app !== null && $app->bound(LoggerInterface::class)
                ? $app->make(LoggerInterface::class)
                : new NullLogger;

            $logger->error('teleframe.event.listener_failed', [
                'event' => $event,
                'exception' => $failure,
            ]);
        } catch (Throwable) {
            // Deliberate: see above. A logger that cannot be resolved is not
            // worth converting a contained listener failure into a crash on
            // the read loop.
        }
    }
}
