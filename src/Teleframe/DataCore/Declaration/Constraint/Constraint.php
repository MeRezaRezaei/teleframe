<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\DataCore\Declaration\Constraint;

/**
 * A constraint is a LIMIT on data that carries the logic itself (verbatim
 * 12:02): a field that is `Enum(['act_on','store_only'])` IS a decision the
 * app must enforce. Derived obligations and generated artifacts are the
 * downstream shapes of the same limit.
 */
abstract class Constraint
{
    abstract public function name(): string;        // 'Required', 'Enum', ...

    /** Human rationale — the "logic exception" trace when the core changes. */
    abstract public function description(): string;

    /** Illuminate validation rules for this limit (single field). */
    public function laravelRules(): array
    {
        return [];
    }

    /** Pure value check (no Laravel) — the standalone-smoke path. */
    public function check(mixed $value): bool
    {
        return true;
    }
}
