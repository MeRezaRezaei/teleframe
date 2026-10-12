<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\DataCore\Declaration;

use MeRezaRezaei\Teleframe\DataCore\Declaration\Constraint\Constraint;

/** A declared data shape: `name` + `fields` + `entityConstraints`. Concrete, not abstract. */
final class DataDeclaration
{
    /**
     * @param  array<string, Field>  $fields
     * @param  list<Constraint>  $entityConstraints
     */
    public function __construct(
        public readonly string $name,                    // 'User', 'Message', 'UpdateRoutingRule'
        public readonly string $source,                  // 'tl-mirror:User' | 'core:UpdateRoutingRule'
        public readonly string $description,             // the human intent — drives obligation rationale
        public readonly array $fields,
        public readonly array $entityConstraints = [],   // Unique(...), Relation(...) over the record
    ) {}

    public function field(string $name): ?Field
    {
        return $this->fields[$name] ?? null;
    }
}
