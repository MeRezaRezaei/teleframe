<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\DataCore\Declaration\Constraint;

final class DefaultValue extends Constraint
{
    public function __construct(public readonly mixed $value) {}

    public function name(): string
    {
        return 'DefaultValue';
    }

    public function description(): string
    {
        return "an absent value is not a mistake — the declared default takes its place ('sometimes' semantics live here; the generated map drops the marker)";
    }

    public function laravelRules(): array
    {
        // Plan §1: a default carries 'sometimes' semantics — an absent value
        // is not a mistake. The GENERATED map (§4) drops it (the emitters
        // filter 'sometimes' — a default is a data-shape concern, not a
        // validator rule); the §1 marker stays here, pinned by the tests.
        return ['sometimes'];
    }

    public function check(mixed $value): bool
    {
        // A default never rejects — any value (including absent) passes; the
        // default merely takes the place of an absent value downstream.
        return true;
    }
}
