<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\DataCore\Declaration\Constraint;

final class Unique extends Constraint
{
    /** @param list<string> $fields */
    public function __construct(public readonly array $fields) {}

    public function name(): string
    {
        return 'Unique';
    }

    public function description(): string
    {
        return 'the '.implode(',', $this->fields).' tuple must be unambiguous — the migration index AND the upsert semantics';
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
