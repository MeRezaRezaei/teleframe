<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Requests;

use MeRezaRezaei\Teleframe\Schema\Generated\Validators\UpdateRoutingRuleValidator;
use MeRezaRezaei\Teleframe\Stage\StageFormRequest;

/** Request for UpdateRoutingRule — web leg and stage leg validate identically. */
final class UpdateRoutingRuleRequest extends StageFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return UpdateRoutingRuleValidator::rules();
    }
}
