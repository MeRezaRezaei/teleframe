<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\DataCore\Declaration\Constraint;

final class Integer extends Constraint
{
    public function __construct(private readonly ?int $min = null, private readonly ?int $max = null) {}

    public function name(): string
    {
        return 'Integer';
    }

    public function description(): string
    {
        return 'peer and telegram ids are 64-bit integers; math limits the meaning';
    }

    public function laravelRules(): array
    {
        $r = ['integer'];
        if ($this->min !== null || $this->max !== null) {
            $r[] = 'between:'.($this->min ?? 0).','.($this->max ?? PHP_INT_MAX);
        }

        return $r;
    }

    public function check(mixed $value): bool
    {
        return is_int($value)
            && ($this->min === null || $value >= $this->min)
            && ($this->max === null || $value <= $this->max);
    }
}
