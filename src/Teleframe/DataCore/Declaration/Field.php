<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\DataCore\Declaration;

use MeRezaRezaei\Teleframe\DataCore\Declaration\Constraint\Constraint;

/** Immutable field meta: one column / one wire param / one form key. */
final class Field
{
    /**
     * @param  list<Constraint>  $constraints
     * @param  list<string>  $sourceEdges  knowledge-graph edge list, e.g. ['tl:Message.date', 'business:routing']
     */
    public function __construct(
        public readonly string $name,        // 'peer_id' (column / wire / form key verbatim)
        public readonly string $phpType,     // 'int'|'string'|'bool'|'float'|'array'|FQCN of a type DTO
        public readonly array $constraints = [],
        public readonly bool $required = true,
        public readonly array $sourceEdges = [],
    ) {}
}
