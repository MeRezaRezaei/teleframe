<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\DataCore\Indication;

use MeRezaRezaei\Teleframe\DataCore\Obligation\Obligation;

/**
 * Maps one obligation to its indication (plan §1 diagram) — pure,
 * deterministic, no I/O, no Laravel: the same obligation always yields the
 * same indication. PersistenceObligation ('persist') has NO indication in
 * this slice — it feeds the migration generator's data source directly — so
 * it maps to null, as does any not-yet-known kind: the mapping is total and
 * never throws. The match arms are the only kind↔class table.
 */
final class IndicationFactory
{
    public function for(Obligation $obligation): ?Indication
    {
        return match ($obligation->kind) {
            'validate' => new ValidatorIndication($obligation),
            'request' => new RequestIndication($obligation),
            'notify' => new EventIndication($obligation),
            default => null,   // 'persist' (and any future kind): no indication in this slice
        };
    }
}
