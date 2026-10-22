<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Realtime;

use phpcent\Client;

/**
 * Centrifugo publisher backed by the official phpcent client library.
 *
 * BOTH TIMEOUTS ARE SET, and that is the whole point of the class. phpcent
 * applies `CURLOPT_CONNECTTIMEOUT` / `CURLOPT_TIMEOUT` only `if ($this->...TimeoutOption)`,
 * so an unconfigured client carries NO curl deadline at all and waits on the OS
 * default. A publish that hangs is not a `Throwable`: the ingest listener's
 * `catch (Throwable)` cannot see it, so no strike is recorded, no log line is
 * written, the Redis entry is never acked, and the transaction never closes —
 * ONE unreachable Centrifugo stalls the mirror for every account that consumer
 * serves. A bounded timeout converts that unbounded stall into a caught,
 * logged, DROPPED projection, which is the correct direction: the listener's
 * own docblock says realtime is a disposable projection and the mirror is the
 * truth. The trade-off is explicit — a Centrifugo slower than the deadline now
 * loses updates instead of stalling the ingest loop.
 *
 * The defaults are an order of magnitude below `QueuedNotifyJob`'s 60s budget
 * for a full Telegram RPC round-trip: a realtime fan-out is fire-and-forget
 * and must never compete with ingest for a connection slot.
 */
final class HttpCentrifugoBridge implements CentrifugoBridge
{
    /** Seconds to wait for the TCP/TLS handshake before giving up on the hop. */
    public const DEFAULT_CONNECT_TIMEOUT_SECONDS = 2;

    /** Seconds for the WHOLE publish request, connect included. */
    public const DEFAULT_TIMEOUT_SECONDS = 5;

    private readonly Client $client;

    public function __construct(
        string $url,
        string $apikey = '',
        string $secret = '',
        int $connectTimeoutSeconds = self::DEFAULT_CONNECT_TIMEOUT_SECONDS,
        int $timeoutSeconds = self::DEFAULT_TIMEOUT_SECONDS,
    ) {
        $client = new Client($url, $apikey, $secret);

        // A non-positive value would be falsy in phpcent's `if ($this->...)`
        // guard and silently reinstate the unbounded wait, so clamp rather than
        // trust the caller.
        $client->setConnectTimeoutOption(max(1, $connectTimeoutSeconds));
        $client->setTimeoutOption(max(1, $timeoutSeconds));

        $this->client = $client;
    }

    public function publish(string $channel, array $payload): void
    {
        $this->client->publish($channel, $payload);
    }
}
