<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Ingest;

use Illuminate\Contracts\Events\Dispatcher;
use MeRezaRezaei\Teleframe\Ingest\Events\RoutingSettingsChanged;
use MeRezaRezaei\Teleframe\Laravel\Models\UpdateRoutingRule;

/**
 * Routing settings observer — the verbatim's settings-change event loop.
 *
 * Owner verbatim 2026-09-14: "the settings are stored in our database
 * tooo and on change there is also the observer that listens to them and
 * this time after updating it sends the update as an event to the defined
 * event path so the other parts of the app now have the new update...
 * the redis two also for chainging the things that the ingester should
 * send as event too so we can controll the things we are going to listen
 * on or ignore in real time".
 *
 * Hooks the Eloquent model lifecycle of UpdateRoutingRule (DB source of
 * truth): after create/update/delete it (1) refreshes the Redis-2
 * hot-reload cache so the daemon's hot path never reads stale rules and
 * (2) dispatches RoutingSettingsChanged — the defined event path other
 * parts of the app listen on to react to listen/ignore reconfiguration
 * in real time.
 */
final class RoutingSettingsObserver
{
    public function __construct(
        private readonly UpdateRoutingCache $cache,
        private readonly ?Dispatcher $events = null,
    ) {}

    public function created(UpdateRoutingRule $rule): void
    {
        $this->sync($rule);
    }

    public function updated(UpdateRoutingRule $rule): void
    {
        $this->sync($rule);
    }

    public function deleted(UpdateRoutingRule $rule): void
    {
        // After delete the row is gone — refresh the account's cache map
        // from whatever rules remain (the deleted one disappears from the
        // hot path), then emit the change event.
        $this->cache->refresh((int) $rule->account_id);
        $this->emit((int) $rule->account_id, (int) $rule->peer_type, (int) $rule->peer_id, 'deleted');
    }

    private function sync(UpdateRoutingRule $rule): void
    {
        $this->cache->refresh((int) $rule->account_id);
        $this->emit((int) $rule->account_id, (int) $rule->peer_type, (int) $rule->peer_id, 'changed');
    }

    private function emit(int $accountId, int $peerType, int $peerId, string $change): void
    {
        $this->events?->dispatch(new RoutingSettingsChanged($accountId, $peerType, $peerId, $change));
    }
}
