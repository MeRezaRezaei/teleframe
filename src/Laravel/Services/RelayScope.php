<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Services;

/**
 * A live user-client scope bound to one relayed vault account: the value
 * the AccountRelay's scope resolver returns. Deliberately tiny so the
 * session string cannot leak out of the resolver — the client is built
 * FROM the vault session, but this holder never carries the session
 * itself and nothing here is ever logged.
 */
final class RelayScope
{
    public bool $connected = false;

    public function __construct(
        public readonly object $client,
        public readonly int $dcId,
    ) {}

    /**
     * Marker: the explicit dial separator. Building a scope never dials,
     * and connect() must not either — TeleframeClient::user() only wraps a
     * SessionData in a UserAccountScope/MTProtoClient, and the actual
     * encrypted socket opens lazily on the first RPC call
     * (MTProtoClient::ensureConnection()). Hosts flip $connected once
     * they have driven their own dial/health check.
     */
    public function connect(): void
    {
        // No I/O by design — see class docblock.
    }
}
