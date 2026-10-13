<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Ingest\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Database\Eloquent\Model;

/**
 * Fired after an ingested update's root transaction commits (roadmap
 * contract: events OUT carry Eloquent models; the app layer consumes).
 *
 * The "after … commits" clause is now ENFORCED, not just documented. This
 * event implements ShouldDispatchAfterCommit, so
 * Illuminate\Events\Dispatcher::dispatch() registers the listeners as a
 * database transaction callback and returns without invoking them
 * (`Dispatcher::dispatch()`, vendor: `if ($isEventObject &&
 * $parsedPayload[0] instanceof ShouldDispatchAfterCommit &&
 * ! is_null($transactions = $this->resolveTransactionManager())) {
 * $transactions->addCallback(fn () => $this->invokeListeners(...));
 * return null; }`). It had to become enforced: the mirror path emits this
 * event from INSIDE the transaction opened by
 * Laravel\MirrorIngesterFactory, so a listener that threw afterwards —
 * UpdateStoredHandler rethrows by design, `Handler\UpdateDispatcher`:
 * `throw $e;` — rolled the mirror facts back while the external publish it
 * had already fired did not un-happen. The stored truth and the announced
 * truth disagreed, and the write was lost.
 *
 * WHY THE INTERFACE, on the EVENT and not on each of the three listeners:
 * one marker on the one event class defers every listener of that event at
 * once. ShouldHandleEventsAfterCommit would mean three independent edits
 * that can drift apart — and a listener registered without the marker is
 * exactly the failure this bug already demonstrated once.
 *
 * The contract this replaced ("plain DTO by design: no framework traits")
 * was already not literally true — the class has always carried an
 * Illuminate\Database\Eloquent\Model — and it was not safely true either,
 * since nothing stopped a host from dispatching it inside a transaction.
 * What remains true, and is kept: there are no dispatching/broadcasting
 * traits here, the event stays a plain value object, and it dispatches
 * through any Illuminate\Contracts\Events\Dispatcher — the Laravel host
 * wires app('events'), plain-PHP hosts wire their own (a dispatcher with no
 * transaction manager simply invokes listeners immediately, which is the
 * correct answer when there is no transaction to wait for). Stays the ONE
 * stored-update event (unification spec D8).
 *
 * Carries any Eloquent model — the legacy Tl* domain models OR the NF5
 * mirror models (TfMirrorModel) — so both ingest paths emit the same event
 * (owner verbatim 2026-09-14: "the data after update comes back in shape
 * of eloquent models").
 */
final class UpdateStored implements ShouldDispatchAfterCommit
{
    public function __construct(
        public readonly Model $model,
        public readonly int $accountId,
    ) {}
}
