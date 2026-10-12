<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\DataCore\Declaration\Constraint;

final class Enum extends Constraint
{
    /**
     * @param  list<string|int>  $allowed
     */
    public function __construct(public readonly array $allowed) {}

    public function name(): string
    {
        return 'Enum';
    }

    public function description(): string
    {
        return "the field may only be one of the declared words — the 'and' algebra of the verbatim";
    }

    public function laravelRules(): array
    {
        return ['in:'.implode(',', array_map('strval', $this->allowed))];
    }

    public function check(mixed $value): bool
    {
        return in_array($value, $this->allowed, true);
    }
}
