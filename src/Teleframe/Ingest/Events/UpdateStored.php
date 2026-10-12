<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Ingest\Events;

use Illuminate\Database\Eloquent\Model;

/**
 * Fired after an ingested update's root transaction commits (roadmap
 * contract: events OUT carry Eloquent models; the app layer consumes).
 *
 * Plain DTO by design (Phase 0): no framework traits, dispatchable through
 * any Illuminate\Contracts\Events\Dispatcher — the Laravel host wires
 * app('events'); plain-PHP hosts wire their own. Stays the ONE stored-update
 * event (unification spec D8).
 *
 * Carries any Eloquent model — the legacy Tl* domain models OR the NF5
 * mirror models (TfMirrorModel) — so both ingest paths emit the same event
 * (owner verbatim 2026-09-14: "the data after update comes back in shape
 * of eloquent models").
 */
final class UpdateStored
{
    public function __construct(
        public readonly Model $model,
        public readonly int $accountId,
    ) {}
}
