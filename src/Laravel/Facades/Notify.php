<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use MeRezaRezaei\Teleframe\Notify\Level;
use MeRezaRezaei\Teleframe\Notify\NotifyCommand;
use MeRezaRezaei\Teleframe\Notify\NotifyService;
use MeRezaRezaei\Teleframe\Notify\PendingNotify;

/**
 * Notify facade (design §3/G9): the notification/logger core surface —
 * "it can notify, it can log, it can report". Routes by topic = predefined
 * log level into the notify chat, runs capability checks (premium honored),
 * splits brief+metadata inline vs long body into a browser-renderable HTML
 * attachment, and sends through ONE shared process (NotifySender) behind
 * the sync queue (default).
 *
 *     Notify::log(Level::Info, 'backup completed', ['bytes' => 48127]);
 *     Notify::to('app.warning', 'ingest stalled', ['account_id' => 7])
 *         ->attachFile('stalled.html', $html, 'text/html')
 *         ->chat('@ops-log', threadId: 12)
 *         ->send();
 *     Notify::report('deploy', ['tasks' => [...]], ['rev' => 'abc123']);
 *     Notify::service();
 *
 * Unconfigured (no teleframe.notify.default_chat): resolves and is callable
 * — sends throw on first transport use, never silent-drop.
 *
 * @method static string log(Level $level, string $brief, array $context = [], ?string $topic = null)
 * @method static PendingNotify to(string $topic, string $brief, array $context = [])
 * @method static string report(string $title, array $sections, array $context = [])
 * @method static NotifyService service()
 *
 * @see NotifyCommand
 */
class Notify extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return NotifyCommand::SERVICE_KEY;
    }

    /**
     * The underlying NotifyService, for the calls the facade does not name.
     *
     * This was advertised in the docblock and in an example but never
     * implemented, and a Laravel Facade is NOT Macroable — so `Notify::service()`
     * raised `Error: Call to undefined method`. A facade whose own example
     * fatals is worse than no facade, because it is trusted. Real now.
     */
    public static function service(): NotifyService
    {
        $resolved = static::getFacadeRoot();

        if (! $resolved instanceof NotifyService) {
            throw new \LogicException(
                'Notify facade is bound to '.(\is_object($resolved) ? $resolved::class : \gettype($resolved))
                .', expected '.NotifyService::class,
            );
        }

        return $resolved;
    }
}
