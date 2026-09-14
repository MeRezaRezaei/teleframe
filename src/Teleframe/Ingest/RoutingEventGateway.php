<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Ingest;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use MeRezaRezaei\Teleframe\Ingest\Events\UpdateStored;
use MeRezaRezaei\Teleframe\Laravel\Models\UpdateRoutingRule;

/**
 * Routing event gateway — the verbatim's "store the final truth, do NOT
 * make it a new event" boundary.
 *
 * Owner verbatim 2026-09-14: "when i make a notify channel for one of the
 * apps i should not handle the updates of that channel as new event but
 * only update that only stores the final truth... so we can controll the
 * things we are going to listen on or ignore in real time".
 *
 * The mirror ALWAYS stores the fact (one half of absolute truth); this
 * gateway decides whether the stored update ALSO becomes an app event
 * (second half). act_on → dispatch UpdateStored (handlers + observable
 * react); store_only → persist silently, no event — the notify/log channel
 * case that would otherwise loop until flood wait.
 *
 * The router consults the DB (or Redis-2 hot-reload cache) source of
 * truth; unclassified peers default to act_on via the router.
 */
final class RoutingEventGateway
{
    public function __construct(
        private readonly UpdateRouter $router,
        private readonly ?Dispatcher $events = null,
    ) {}

    /**
     * Store + conditionally emit. Returns whether the UpdateStored event
     * was dispatched (false = store_only peer, persisted silently).
     *
     * A pre-decided mode (e.g. from SelfOriginatedClassifier, which also
     * knows the out-flag group-2 default) short-circuits the per-peer
     * router lookup so the classifier and gateway can never disagree.
     *
     * @param  array<string, mixed>  $payload  decoded TL update
     * @param  Model  $model  the persisted root model
     * @param  string|null  $decidedMode  UpdateRoutingRule::MODE_* decision, or null to ask the router
     */
    public function emit(int $accountId, array $payload, Model $model, ?string $decidedMode = null): bool
    {
        $mode = $decidedMode ?? $this->router->classify($accountId, $payload);

        if ($mode === UpdateRoutingRule::MODE_STORE_ONLY) {
            return false;
        }

        $this->events?->dispatch(new UpdateStored($model, $accountId));

        return true;
    }
}
