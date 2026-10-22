<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Notify;

/**
 * RateLimitOutcome — the READABLE RECORD of what one send's rate-limit
 * policy did. This is the difference between "a rate limit that is logged
 * and ignored" (which is what the notify core had: `ResilientCall` caught
 * only the MTProto-typed `FloodWaitException`, so a Bot-API 429 and a
 * `SLOWMODE_WAIT_x` were never even counted, let alone honoured) and
 * enforcement whose cost the caller can see.
 *
 * Every number here is a FACT about the send, not an intention:
 *
 *  - `attempts`   how many times the transport was actually dialled.
 *  - `waits`      one entry per honoured wait, in order — the exact
 *                 server-supplied seconds that were slept (or the
 *                 bounded fallback when Telegram demanded a rate limit
 *                 without a readable number, see `knownWait`).
 *  - `knownWait`  false when the server said "rate limited" but the
 *                 demanded seconds could not be read off the error; the
 *                 policy then sleeps the bounded `unknownWaitSeconds`
 *                 fallback instead of guessing, and says so here.
 *  - `deferred`   true when the demanded wait was LONGER than the cap,
 *                 so nothing was slept in-line and the send was handed
 *                 back to the queue/operator instead (the repo posture:
 *                 a worker must not sleep hours inside one call).
 *  - `capSeconds` the in-line cap in force for this send.
 *
 * Plain-PHP constructible, immutable, zero regex. Reaches the caller two
 * ways: `NotificationSender::sendWithReceipt()` carries it on the receipt,
 * and `NotificationSender::lastRateLimit()` carries the last one even when
 * the send threw (which is exactly the deferred/exhausted case, where a
 * receipt does not exist).
 */
final class RateLimitOutcome
{
    /**
     * @param  list<int>  $waits  the seconds actually slept, in order (empty = never rate limited)
     * @param  string  $method  the transport method this outcome describes ('sendMessage', …)
     * @param  int  $attempts  how many times the transport was dialled
     * @param  int  $capSeconds  the in-line cap in force
     * @param  bool  $deferred  true when the demanded wait exceeded the cap and nothing was slept
     * @param  bool  $knownWait  false when the demanded seconds were unreadable and the bounded fallback was used
     */
    public function __construct(
        public readonly string $method,
        public readonly int $attempts,
        public readonly array $waits = [],
        public readonly int $capSeconds = 0,
        public readonly bool $deferred = false,
        public readonly bool $knownWait = true,
    ) {}

    /** Total seconds this send spent asleep inside the cap. */
    public function waitedSeconds(): int
    {
        return array_sum($this->waits);
    }

    /** The longest single wait honoured (0 when there was none). */
    public function longestWait(): int
    {
        return $this->waits === [] ? 0 : max($this->waits);
    }

    /** Whether any rate limit was encountered at all (waited OR deferred). */
    public function limited(): bool
    {
        return $this->waits !== [] || $this->deferred;
    }

    /**
     * The log/metric shape. Deliberately flat and scalar so it can go into
     * a PSR-3 context array or a metrics sink without a serialiser.
     *
     * @return array<string, bool|int|string|list<int>>
     */
    public function toArray(): array
    {
        return [
            'method' => $this->method,
            'attempts' => $this->attempts,
            'waits' => $this->waits,
            'waited_seconds' => $this->waitedSeconds(),
            'cap_seconds' => $this->capSeconds,
            'deferred' => $this->deferred,
            'known_wait' => $this->knownWait,
        ];
    }
}
