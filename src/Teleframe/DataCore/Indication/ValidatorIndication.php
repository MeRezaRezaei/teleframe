<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\DataCore\Indication;

/**
 * "This obligation becomes a generated validator rule-map" — derived from a
 * ValidationObligation (kind 'validate'); a value outside the declaration's
 * limits is a flow shifter and must refuse entry. Carries only the
 * obligation: the derived laravelRules() map is the ValidatorGenerator's job,
 * and the declaration name sits at $this->obligation->declaration for the
 * registry lookup.
 */
final class ValidatorIndication extends Indication
{
    public function name(): string
    {
        return 'ValidatorIndication';
    }
}
