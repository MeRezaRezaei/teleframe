<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\DataCore\Declaration\Constraint;

final class Indexed extends Constraint
{
    public function __construct(public readonly bool $unique = true) {}

    public function name(): string
    {
        return 'Indexed';
    }

    public function description(): string
    {
        return 'the field is looked up by value; the index is a persistence obligation, not a validation rule';
    }

    public function laravelRules(): array
    {
        return [];
    }

    public function check(mixed $value): bool
    {
        return true;
    }
}
