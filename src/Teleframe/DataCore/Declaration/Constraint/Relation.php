<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\DataCore\Declaration\Constraint;

final class Relation extends Constraint
{
    public function __construct(public readonly string $target, public readonly string $localKey) {}

    public function name(): string
    {
        return 'Relation';
    }

    public function description(): string
    {
        return 'this field is the knowledge-graph edge to '.$this->target.' via '.$this->localKey;
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
