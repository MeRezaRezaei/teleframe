<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\DataCore\Declaration\Constraint;

final class Required extends Constraint
{
    public function name(): string
    {
        return 'Required';
    }

    public function description(): string
    {
        return 'a missing value is a flow shifter: the intake must refuse';
    }

    public function laravelRules(): array
    {
        return ['required'];
    }

    public function check(mixed $value): bool
    {
        return $value !== null && $value !== '';
    }
}
