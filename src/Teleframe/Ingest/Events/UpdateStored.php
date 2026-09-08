<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Ingest\Events;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/**
 * Fired after an ingested update's root transaction commits (roadmap
 * contract: events OUT carry Eloquent models; the app layer consumes).
 *
 * Plain DTO by design (Phase 0): no framework traits, dispatchable through
 * any Illuminate\Contracts\Events\Dispatcher — the Laravel host wires
 * app('events'); plain-PHP hosts wire their own. Stays the ONE stored-update
 * event (unification spec D8).
 */
final class UpdateStored
{
    public function __construct(
        public readonly TlInstanceModel $model,
        public readonly int $accountId,
    ) {
    }
}