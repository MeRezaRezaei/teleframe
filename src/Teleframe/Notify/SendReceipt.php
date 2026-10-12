<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Notify;

/**
 * SendReceipt — the result of ONE notification send, with the cost of
 * getting it out.
 *
 * `NotificationSender::send()` returns the raw transport reply, because
 * that is what every existing caller (the queue worker, the PSR-3 bridge,
 * the watchdog) already treats as the send's outcome. A rate limit that
 * made the send sleep 30 seconds, or one that was deferred to the queue
 * because the demanded wait was over the cap, is invisible in a raw
 * reply — which is precisely how "logged and ignored" looks from the
 * outside. `sendWithReceipt()` is the same send with the bookkeeping
 * attached, and `NotificationService::handle()` logs it.
 *
 * Immutable, plain-PHP, zero regex. `toArray()` is flat and scalar so it
 * can be dropped into a PSR-3 context, a metrics sink or a job row
 * without a serialiser.
 */
final class SendReceipt
{
    /**
     * @param  string  $transport  the transport's log-safe label ('bot-api', 'mtproto:user-scope')
     * @param  string  $method  the transport method that carried the send
     * @param  array<string, mixed>  $response  the transport's own reply, untouched
     * @param  RateLimitOutcome|null  $rateLimit  what the rate-limit policy did, null when it never engaged
     */
    public function __construct(
        public readonly string $transport,
        public readonly string $method,
        public readonly array $response,
        public readonly ?RateLimitOutcome $rateLimit = null,
    ) {}

    /** Seconds this send spent asleep inside the rate-limit cap. */
    public function waitedSeconds(): int
    {
        return $this->rateLimit?->waitedSeconds() ?? 0;
    }

    /** Whether the send hit a rate limit at all (honoured or deferred). */
    public function rateLimited(): bool
    {
        return $this->rateLimit?->limited() ?? false;
    }

    /**
     * The receipt's own shape, and the transport's reply merged under a
     * namespaced key so a caller that needs the raw reply can still find
     * it without a second call.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'transport' => $this->transport,
            'method' => $this->method,
            'response' => $this->response,
            'rate_limit' => $this->rateLimit?->toArray(),
        ];
    }
}
