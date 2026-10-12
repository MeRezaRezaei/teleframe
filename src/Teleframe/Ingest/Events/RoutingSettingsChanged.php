<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Ingest\Events;

use MeRezaRezaei\Teleframe\Laravel\Models\UpdateRoutingRule;

/**
 * Fired after a routing setting changes in the DB — the verbatim's
 * "on change the observer... sends the update as an event to the defined
 * event path".
 *
 * Owner verbatim 2026-09-14: "...the other parts of the app now have the
 * new update procced and stored and the data in models to query so they
 * now can act up on the update smoothly".
 *
 * Carries the hydrated UpdateRoutingRule model snapshot (the source-of-
 * truth row as it is right now), so subscribers act on model data without
 * re-querying — created/updated carry the fresh row; deleted carries the
 * row as it was before deletion (Eloquent keeps attributes after delete).
 *
 * Plain DTO by design (Phase 0 convention): no framework traits, dispatchable
 * through any Illuminate\Contracts\Events\Dispatcher. App layers subscribe
 * to react to listen/ignore reconfiguration in real time (Redis-2 hot
 * reload, daemon re-read, UI refresh).
 */
final class RoutingSettingsChanged
{
    public function __construct(
        public readonly UpdateRoutingRule $rule,
        public readonly string $change,
    ) {}

    public function accountId(): int
    {
        return (int) $this->rule->account_id;
    }

    public function peerType(): int
    {
        return (int) $this->rule->peer_type;
    }

    public function peerId(): int
    {
        return (int) $this->rule->peer_id;
    }
}
