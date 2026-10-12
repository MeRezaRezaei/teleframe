<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\DataCore\Declaration\Constraint;

final class Range extends Constraint
{
    public function __construct(private readonly ?int $min = null, private readonly ?int $max = null) {}

    public function name(): string
    {
        return 'Range';
    }

    public function description(): string
    {
        return 'a numeric band is a limit shaped as a decision: outside the band is a flow shifter';
    }

    public function laravelRules(): array
    {
        $r = [];
        if ($this->min !== null || $this->max !== null) {
            $r[] = 'between:'.($this->min ?? 0).','.($this->max ?? PHP_INT_MAX);
        }

        return $r;
    }

    public function check(mixed $value): bool
    {
        return (is_int($value) || is_float($value))
            && ($this->min === null || $value >= $this->min)
            && ($this->max === null || $value <= $this->max);
    }
}
