<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\DataCore\Obligation;

/**
 * An obligation is a derived "the app MUST" statement: data plus constraints
 * makes the core obligations (plan §2). One declaration derives zero or more
 * obligations through ObligationFactory::for() — the derivation is pure,
 * deterministic, and table-driven (no I/O, no Laravel).
 *
 * The three columns are the knowledge-graph edge out of the declaration:
 * the kind ('validate' | 'persist' | 'notify' | 'request'), the declaration
 * name it derives from, and the human rationale — the "logic exception"
 * trace back to the constraint(s) that forced the obligation into existence.
 */
abstract class Obligation
{
    public function __construct(
        public readonly string $kind,          // 'validate' | 'persist' | 'notify' | 'request'
        public readonly string $declaration,   // declaration name — the knowledge-graph edge
        public readonly string $rationale,     // the verbatim's "logic exception" trace to the constraint(s)
    ) {}
}
