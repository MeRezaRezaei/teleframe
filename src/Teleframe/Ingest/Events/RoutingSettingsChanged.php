<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Ingest\Events;

/**
 * Fired after a routing setting changes in the DB — the verbatim's
 * "on change the observer... sends the update as an event to the defined
 * event path".
 *
 * Plain DTO by design (Phase 0 convention): no framework traits, dispatchable
 * through any Illuminate\Contracts\Events\Dispatcher. App layers subscribe
 * to react to listen/ignore reconfiguration in real time (Redis-2 hot
 * reload, daemon re-read, UI refresh).
 */
final class RoutingSettingsChanged
{
    public function __construct(
        public readonly int $accountId,
        public readonly int $peerType,
        public readonly int $peerId,
        public readonly string $change,
    ) {}
}
