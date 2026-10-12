<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\DataCore\Declaration\Constraint;

final class StringValue extends Constraint
{
    public function __construct(private readonly ?int $minLength = null, private readonly ?int $maxLength = null) {}

    public function name(): string
    {
        return 'StringValue';
    }

    public function description(): string
    {
        return 'a string is a meaning bounded by length; the limits are the decision';
    }

    public function laravelRules(): array
    {
        $r = ['string'];
        if ($this->minLength !== null) {
            $r[] = 'min:'.$this->minLength;
        }
        if ($this->maxLength !== null) {
            $r[] = 'max:'.$this->maxLength;
        }

        return $r;
    }

    public function check(mixed $value): bool
    {
        return is_string($value)
            && ($this->minLength === null || strlen($value) >= $this->minLength)
            && ($this->maxLength === null || strlen($value) <= $this->maxLength);
    }
}
