<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Validators;

use MeRezaRezaei\Teleframe\Stage\StageValidator;

/** Validator for UpdateRoutingRule (derived from core:UpdateRoutingRule by ValidatorGenerator). */
final class UpdateRoutingRuleValidator
{
    /** @return array<string, list<mixed>> — one source, every leg. */
    public static function rules(): array
    {
        return [
            'account_id' => ['required', 'integer'],
            'peer_type'  => ['required', 'integer', 'between:0,255'],
            'peer_id'    => ['required', 'integer'],
            'mode'       => ['required', 'in:act_on,store_only'],
            'priority'   => ['integer', 'between:0,255'],
        ];
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public static function validate(array $data): array
    {
        return (new StageValidator())->data($data, self::rules());
    }
}
