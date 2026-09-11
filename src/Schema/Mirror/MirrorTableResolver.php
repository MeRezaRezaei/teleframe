<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlConstructor;
use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlParam;
use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlScheme;
use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlType;
use MeRezaRezaei\Teleframe\Schema\Mirror\Ddl\MirrorColumn;
use MeRezaRezaei\Teleframe\Schema\Mirror\Ddl\MirrorColumnType;

/**
 * Builds the full DDL graph from catalog + TL scheme.
 *
 * Given a list of parent table names, walks the catalog children,
 * decomposes every FK→Target and 1:N child into child MirrorTable nodes,
 * resolves vector elements from the TL scheme, and returns a deterministic
 * ordered list of parent MirrorTable objects — each containing its full child subtree.
 */
final class MirrorTableResolver
{
    /** Collected MirrorTable instances keyed by tfName, in resolution order. */
    private array $tables = [];

    public function __construct(
        private readonly MirrorCatalog $catalog,
        private readonly TlScheme $scheme,
    ) {}

    /** @return list<MirrorTable> */
    public function resolveAll(array $parentTfNames): array
    {
        $this->tables = [];
        $parents = [];
        foreach ($parentTfNames as $tfName) {
            $parents[] = $this->resolveTable($tfName, parentContext: null);
        }
        return $parents;
    }

    /**
     * Resolve a catalog entry to an MirrorTable with all children nested.
     *
     * @param array{keyColumns: list<string>, parentTf: string}|null $parentContext inherited key from the referrer
     */
    private function resolveTable(string $tfName, ?array $parentContext, bool $forceParent = false): MirrorTable
    {
        if (isset($this->tables[$tfName])) {
            return $this->tables[$tfName];
        }

        $entry = $this->catalog->table($tfName);
        $keyCols = $parentContext['keyColumns'] ?? $this->deriveKeyColumns($entry);

        // T4b: mirror tables resolved via FK are always parents (they have their own PK).
        $isParent = $forceParent || ($parentContext === null);
        $constructor = count($entry->ctors) > 1 ? 'constructor' : '';
        $columns = $this->buildColumns($entry, $keyCols, $constructor);
        $children = [];

        foreach ($entry->children as [$fieldName, $shape]) {
            $child = $this->resolveChildField($tfName, $fieldName, $shape, $keyCols);
            if ($child !== null) {
                $children[] = $child;
            }
        }

        $table = new MirrorTable(
            tfName: $tfName,
            tlType: $entry->tlType,
            parent: $isParent,
            positioned: false,
            parentTf: $parentContext['parentTf'] ?? '',
            keyColumns: $keyCols,
            columns: $columns,
            children: $children,
            constructor: $constructor,
            peerColumns: $this->findPeerCols($columns),
            hexColumns: $this->findHexCols($columns),
            booleanColumns: $this->findBoolCols($columns),
        );
        $this->tables[$tfName] = $table;
        return $table;
    }

    /**
     * Derive key column names from a catalog entry's base shape.
     *
     * T4d: peer-first check BEFORE empty-base check.
     * T4e: zero-regex — singularizeParentTf replaces preg_replace.
     */
    private function deriveKeyColumns(MirrorTableEntry $entry): array
    {
        // T4d: check peer first (F2)
        if ($entry->base !== [] && $entry->base[0][0] === 'peer') {
            return ['peer_type', 'peer_id'];
        }
        if ($entry->base === []) {
            // Empty-base child-keyed: derive from singularized table name.
            $raw = $this->singularizeParentTf($entry->tfName);
            return ["{$raw}_id"];
        }
        return ['id'];
    }

    /**
     * T4e: zero-regex singularization of parent tf name.
     *
     * Strip 'tf_' prefix, map known plurals to singulars, then strip trailing 's'.
     */
    private function singularizeParentTf(string $tfName): string
    {
        $raw = str_starts_with($tfName, 'tf_') ? substr($tfName, 3) : $tfName;
        $suffixes = ['_medias' => '_media', '_actions' => '_action', '_entities' => '_entity'];
        foreach ($suffixes as $from => $to) {
            if (str_ends_with($raw, $from)) {
                return substr($raw, 0, -strlen($from)) . $to;
            }
        }
        if (str_ends_with($raw, 's') && strlen($raw) > 1) {
            return substr($raw, 0, -1);
        }
        return $raw;
    }

    /**
     * @param list<string> $parentKeyCols
     */
    private function resolveChildField(string $parentTf, string $fieldName, string $shape, array $parentKeyCols): ?MirrorTable
    {
        if (str_starts_with($shape, 'FK→')) {
            // FK→ is 5 bytes in UTF-8 (2 ASCII + 3 for U+2192)
            $targetType = substr($shape, 5);
            return $this->resolveFkTarget($parentTf, $fieldName, $targetType, $parentKeyCols);
        }
        if ($shape === '1:N child') {
            return $this->resolveVectorChild($parentTf, $fieldName, $parentKeyCols);
        }
        // Scalar child: single-column child table (rare)
        // parentTf already includes 'tf_' prefix, so child is just parentTf_fieldName
        $childTfName = "{$parentTf}_{$fieldName}";
        $columns = $this->makeChildPkCols($parentKeyCols);
        $resolved = MirrorFieldDecomposer::fromShape($shape, $fieldName);
        if ($resolved === null) {
            return null;
        }
        $columns = array_merge($columns, $resolved);
        return $this->makeChildTable($childTfName, '', $parentKeyCols, $columns, false, $parentTf);
    }

    /**
     * @param list<string> $parentKeyCols
     */
    private function resolveFkTarget(string $parentTf, string $fieldName, string $targetType, array $parentKeyCols): ?MirrorTable
    {
        $mirror = $this->catalog->tableForTlType($targetType);
        // T4b: mirror tables with non-empty base are always parents.
        if ($mirror !== null && $mirror->base !== []) {
            return $this->resolveTable($mirror->tfName, [
                'keyColumns' => $parentKeyCols,
                'parentTf'   => $parentTf,
            ], forceParent: true);
        }
        // Non-mirror or empty-base: decompose from the minimal ctor.
        $tlType = $this->scheme->types()[$targetType] ?? null;
        if ($tlType === null) {
            return null;
        }
        $minimalCtor = $this->minimalCtor($tlType->constructors());
        if ($minimalCtor === null) {
            return null;
        }
        // parentTf already includes 'tf_' prefix; mirror->tfName also has it
        $childTfName = ($mirror !== null) ? $mirror->tfName : "{$parentTf}_{$fieldName}";
        $columns = $this->makeChildPkCols($parentKeyCols);
        foreach ($minimalCtor->params() as $param) {
            $shape = MirrorFieldDecomposer::shapeForParam($param);
            $cols = MirrorFieldDecomposer::fromShape($shape, $param->name, $param->baseType() === 'bytes');
            if ($cols !== null) {
                $columns = array_merge($columns, $cols);
            }
        }
        $constructor = count($tlType->constructors()) > 1 ? 'constructor' : '';
        return $this->makeChildTable($childTfName, $constructor, $parentKeyCols, $columns, false, $parentTf);
    }

    /**
     * Resolve a 1:N vector child field.
     *
     * @param list<string> $parentKeyCols
     */
    private function resolveVectorChild(string $parentTf, string $fieldName, array $parentKeyCols): ?MirrorTable
    {
        $parentEntry = $this->catalog->table($parentTf);
        $tlType = $this->scheme->types()[$parentEntry->tlType] ?? null;
        if ($tlType === null) {
            return null;
        }
        $fieldParam = $this->findParam($tlType->constructors(), $fieldName);
        if ($fieldParam === null) {
            return null;
        }
        // Vector<X> — baseType is X
        $elementType = $fieldParam->baseType();
        $elementTlType = $this->scheme->types()[$elementType] ?? null;
        if ($elementTlType === null) {
            return null;
        }
        $minimalCtor = $this->minimalCtor($elementTlType->constructors());
        if ($minimalCtor === null) {
            return null;
        }
        // parentTf already includes 'tf_' prefix
        $childTfName = "{$parentTf}_{$fieldName}";
        $columns = $this->makeChildPkCols($parentKeyCols);
        $columns[] = new MirrorColumn('position', MirrorColumnType::TinyInt);
        foreach ($minimalCtor->params() as $param) {
            $shape = MirrorFieldDecomposer::shapeForParam($param);
            $cols = MirrorFieldDecomposer::fromShape($shape, $param->name, $param->baseType() === 'bytes');
            if ($cols !== null) {
                $columns = array_merge($columns, $cols);
            }
        }
        $constructor = count($elementTlType->constructors()) > 1 ? 'constructor' : '';
        return $this->makeChildTable($childTfName, $constructor, $parentKeyCols, $columns, true, $parentTf);
    }

    /**
     * Find the ctor with the fewest params (the minimal/most basic constructor).
     *
     * @param array<string, TlConstructor> $constructors
     */
    private function minimalCtor(array $constructors): ?TlConstructor
    {
        $best = null;
        $bestCount = PHP_INT_MAX;
        foreach ($constructors as $ctor) {
            $count = count($ctor->params());
            if ($count < $bestCount) {
                $best = $ctor;
                $bestCount = $count;
            }
        }
        return $best;
    }

    /**
     * Find a param by name across all constructors of a type.
     *
     * @param array<string, TlConstructor> $constructors
     */
    private function findParam(array $constructors, string $name): ?TlParam
    {
        foreach ($constructors as $ctor) {
            if (isset($ctor->params()[$name])) {
                return $ctor->params()[$name];
            }
        }
        return null;
    }

    /**
     * @param list<string> $parentKeyCols
     * @return list<MirrorColumn>
     */
    private function makeChildPkCols(array $parentKeyCols): array
    {
        $cols = [new MirrorColumn('account_id', MirrorColumnType::BigInt)];
        foreach ($parentKeyCols as $col) {
            $cols[] = new MirrorColumn($col, MirrorColumnType::BigInt);
        }
        return $cols;
    }

    /**
     * @param list<MirrorColumn> $columns
     */
    private function makeChildTable(
        string $tfName,
        string $constructor,
        array $parentKeyCols,
        array $columns,
        bool $positioned,
        string $parentTf,
    ): MirrorTable {
        return new MirrorTable(
            tfName: $tfName,
            tlType: '',
            parent: true, // child tables are standalone DDL-creatable
            positioned: $positioned,
            parentTf: $parentTf,
            keyColumns: $parentKeyCols,
            columns: $columns,
            children: [],
            constructor: $constructor,
            peerColumns: $this->findPeerCols($columns),
            hexColumns: $this->findHexCols($columns),
            booleanColumns: $this->findBoolCols($columns),
        );
    }

    /**
     * Build columns for a parent table from its catalog entry.
     *
     * @param list<string> $keyCols
     * @return list<MirrorColumn>
     */
    private function buildColumns(MirrorTableEntry $entry, array $keyCols, string $constructor): array
    {
        $columns = [];
        $columns[] = new MirrorColumn('account_id', MirrorColumnType::BigInt);
        foreach ($keyCols as $col) {
            $columns[] = new MirrorColumn($col, MirrorColumnType::BigInt);
        }
        if ($constructor !== '') {
            $columns[] = new MirrorColumn($constructor, MirrorColumnType::String);
        }
        foreach ($entry->base as [$name, $shape]) {
            if (in_array($name, $keyCols, true)) {
                continue;
            }
            $resolved = MirrorFieldDecomposer::fromShape($shape, $name);
            if ($resolved !== null) {
                $columns = array_merge($columns, $resolved);
            }
        }
        foreach ($entry->bools as $bool) {
            $columns[] = new MirrorColumn($bool, MirrorColumnType::Boolean);
        }
        return $columns;
    }

    /**
     * @param list<MirrorColumn> $columns
     * @return list<array{kind:string, name:string}>
     */
    private function findPeerCols(array $columns): array
    {
        return array_values(array_map(
            fn (MirrorColumn $c) => ['name' => $c->name, 'kind' => str_ends_with($c->name, '_type') ? 'type' : 'id'],
            array_filter($columns, static fn (MirrorColumn $c) => $c->peer),
        ));
    }

    /**
     * @param list<MirrorColumn> $columns
     * @return list<string>
     */
    private function findHexCols(array $columns): array
    {
        return array_map(
            static fn (MirrorColumn $c) => $c->name,
            array_filter($columns, static fn (MirrorColumn $c) => $c->hex),
        );
    }

    /**
     * @param list<MirrorColumn> $columns
     * @return list<string>
     */
    private function findBoolCols(array $columns): array
    {
        return array_map(
            static fn (MirrorColumn $c) => $c->name,
            array_filter($columns, static fn (MirrorColumn $c) => $c->type === MirrorColumnType::Boolean),
        );
    }
}
