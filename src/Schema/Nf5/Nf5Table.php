<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Nf5;

use MeRezaRezaei\Teleframe\Schema\Nf5\Ddl\Nf5Column;

/**
 * Resolved DDL graph node — one per mirror or synthesized table.
 *
 * Each Nf5Table carries its full child subtree (children are themselves
 * Nf5Table instances) so the caller can walk the graph top-down.
 */
final class Nf5Table
{
    /**
     * @param string              $tfName          Mirror table name (e.g. 'tf_users')
     * @param string              $tlType          TL type this table maps to (e.g. 'User')
     * @param bool                $parent          True if this table is a DDL-creatable parent (has its own PK)
     * @param bool                $positioned      True for 1:N vector children (includes a 'position' column)
     * @param string              $parentTf        Parent table name (empty for top-level parents)
     * @param list<string>        $keyColumns      Primary key column names inherited from the parent
     * @param list<Nf5Column>     $columns         All columns including PK, discriminator, base, and bools
     * @param list<Nf5Table>      $children        Nested child tables
     * @param string              $constructor     Discriminator column name (empty if single ctor)
     * @param list<array{kind:string, name:string}> $peerColumns  Peer-typed columns (type/id pairs)
     * @param list<string>        $hexColumns      Column names stored as hex (bytes fields)
     * @param list<string>        $booleanColumns  Column names of boolean type
     */
    public function __construct(
        public readonly string $tfName,
        public readonly string $tlType,
        public readonly bool $parent,
        public readonly bool $positioned,
        public readonly string $parentTf,
        public readonly array $keyColumns,
        public readonly array $columns,
        public readonly array $children,
        public readonly string $constructor,
        public readonly array $peerColumns,
        public readonly array $hexColumns,
        public readonly array $booleanColumns,
    ) {}
}
