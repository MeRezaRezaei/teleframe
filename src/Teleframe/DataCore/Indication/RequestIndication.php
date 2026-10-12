<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\DataCore\Indication;

/**
 * "This obligation becomes a generated request" — derived from a
 * RequestObligation (kind 'request'): data arrives from a leg (web form or
 * stage update) and one rules() serves both. Carries only the obligation;
 * the generated FormRequest's rules() delegates to the sibling validator, and
 * the declaration name sits at $this->obligation->declaration.
 */
final class RequestIndication extends Indication
{
    public function name(): string
    {
        return 'RequestIndication';
    }
}
