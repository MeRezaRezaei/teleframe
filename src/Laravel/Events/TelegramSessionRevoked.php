<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Events;

use Illuminate\Support\Facades\Event;

use MeRezaRezaei\Teleframe\Laravel\Events\Concerns\ConcernsDispatchEvent;

/**
 * Event dispatched when the server REJECTS an account's auth key
 * (AUTH_KEY_UNREGISTERED / AUTH_KEY_INVALID / SESSION_REVOKED / SESSION_EXPIRED)
 * and that revocation has been recorded on the vault row.
 *
 * WHY A HOST NEEDS THIS RATHER THAN A POLL. The poller already rethrows the
 * AuthKeyException (see UpdatePollerService::errorLoopAction) — that tells
 * whoever is holding the loop, and only for as long as they are. A long-lived
 * app still has to answer "which of my accounts just died?" without wrapping
 * every polling entry point and without keeping the exception in memory.
 * Recording the revocation makes the state durable; this event makes the
 * TRANSITION observable exactly once, so a host can page someone, mark an
 * onboarding flow broken, or re-drive a login UI.
 *
 * PAYLOAD. `accountId` is the Telegram user id (the identity the auth key
 * belongs to), which is the same value the poller passes as `account_id` to
 * TelegramGapDetected / TelegramResynced. It is NOT the vault row id, because
 * a bot or a session-only scope has no row — the Telegram id is the one
 * identifier every consumer of the other two events already has.
 *
 * NEVER A SESSION VALUE. Nothing about the revoked key travels in this event
 * — no session string, no auth_key_id, no RPC error text. `revoked_at` on the
 * vault row is the durable record; the event is only the notification.
 */
class TelegramSessionRevoked
{
    use ConcernsDispatchEvent;

    /**
     * @param  int  $accountId  Telegram user id whose auth key the server rejected
     */
    final public function __construct(
        public readonly int $accountId
    ) {}

    /**
     * Dispatch the event if a Laravel event dispatcher is available.
     */
    public static function dispatch(int $accountId): void
    {
        if (class_exists(Event::class) && Event::getFacadeApplication()) {
            self::dispatchTelegramEvent(new static($accountId));
        }
    }
}
