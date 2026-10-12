<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\DataCore\Indication;

/**
 * "This obligation becomes a generated event DTO" — derived from an
 * EventObligation (kind 'notify'): changes to the declared data must notify
 * subscribers in real time; silence would keep stale truth. Carries only the
 * obligation; the event's field shapes come from the declaration the
 * generator resolves via $this->obligation->declaration.
 */
final class EventIndication extends Indication
{
    public function name(): string
    {
        return 'EventIndication';
    }
}
