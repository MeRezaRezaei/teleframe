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
 *
 * Invariants enforced here (NF5 spec §5/§7):
 *  - Parents are resolved context-free ONCE — FK resolution never re-enters a
 *    catalog parent, so a target can never inherit a referrer's key columns.
 *  - FK→T where T is a catalog parent → a 1:1 *reference child*
 *    holding only the target's handle + a RESTRICT FK to the target parent.
 *  - FK→ non-catalog type (and vector elements) → *union decomposition* of the
 *    ctor set (all params that exist in ANY variant, NOT the minimal ctor),
 *    plus a real `constructor` discriminator column when there is >1 ctor.
 *  - Bare `flags`/`flags2` bitmask params are never materialized as columns.
 */
final class MirrorTableResolver
{
    /** Collected parent MirrorTable instances keyed by tfName, in resolution order. */
    private array $tables = [];

    /** Ancestor tlType names of the union-decomposition currently in progress. */
    private array $unionStack = [];

    public function __construct(
        private readonly MirrorCatalog $catalog,
        private readonly TlScheme $scheme,
    ) {}

    /** @return list<MirrorTable> */
    public function resolveAll(array $parentTfNames): array
    {
        $this->tables = [];
        $this->unionStack = [];
        $parents = [];
        foreach ($parentTfNames as $tfName) {
            $parents[] = $this->resolveTable($tfName);
        }
        return $parents;
    }

    /**
     * Resolve a catalog entry to an MirrorTable with all children nested.
     * Parents are ONLY ever resolved with this context-free path.
     */
    private function resolveTable(string $tfName): MirrorTable
    {
        if (isset($this->tables[$tfName])) {
            return $this->tables[$tfName];
        }

        $entry = $this->catalog->table($tfName);
        $keyCols = $this->deriveKeyColumns($entry);
        $constructor = count($entry->ctors) > 1 ? 'constructor' : '';
        $columns = $this->buildBaseColumns($entry, $keyCols, $constructor);
        $children = [];

        // Seed the union ancestry with the parent's own type so a child union
        // referencing back to the parent's tlType (or to another catalog parent
        // that is later expanded) terminates instead of recursing infinitely.
        $this->unionStack[] = $entry->tlType;

        // Base entries: scalar shapes become columns (buildBaseColumns already
        // handled those); only FK→ / 1:N child shapes become child tables.
        foreach ($entry->base as [$fieldName, $shape]) {
            if ($keyCols !== [] && $this->shapeExpandsIntoKeyCols($shape, $fieldName, $keyCols)) {
                continue;
            }
            if ($this->isFactShape($shape)) {
                $child = $this->resolveChildField($tfName, $fieldName, $shape, $keyCols);
                if ($child !== null) {
                    $children[] = $child;
                }
            }
        }
        // Children entries declare ANY shape (scalar, peer, FK→, 1:N). The
        // catalog already marked them as facts, so every one becomes a child
        // table — scalars/peers become single-column holds, never dropped.
        foreach ($entry->children as [$fieldName, $shape]) {
            if ($keyCols !== [] && $this->shapeExpandsIntoKeyCols($shape, $fieldName, $keyCols)) {
                continue;
            }
            $child = $this->resolveChildField($tfName, $fieldName, $shape, $keyCols);
            if ($child !== null) {
                $children[] = $child;
            }
        }

        array_pop($this->unionStack);

        $table = new MirrorTable(
            tfName: $tfName,
            tlType: $entry->tlType,
            parent: true,
            positioned: false,
            parentTf: '',
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
     * The FIRST base column decides the scope (mirrors the catalog's own
     * invariant: base[0] is 'id' or 'peer'):
     *  1. base[0] = 'id'     → ['id'] (tf_messages, tf_users, tf_todo_items, ...)
     *  2. base[0] = 'peer'   → the expanded peer discriminator columns
     *     (peer_type/peer_id — tf_dialogs, tf_folders, tf_saved_dialogs).
     *  3. Any other base[0]  → the catalog entry has no self-identifying
     *     natural key (tf_attach_menu_bots.bot_id, structural stores like
     *     tf_todo_lists whose real identity is contextual): synthetic
     *     `{singular}_id` from the table name.
     *  4. Empty base         → synthetic `{singular}_id` (tf_message_medias,
     *     tf_message_entities — union stores keyed by context, see spec §41).
     */
    private function deriveKeyColumns(MirrorTableEntry $entry): array
    {
        if ($entry->base !== []) {
            $first = $entry->base[0];
            if ($first[0] === 'id' || $first[0] === 'peer') {
                if ($first[0] === 'peer') {
                    $expanded = MirrorFieldDecomposer::fromShape($first[1], $first[0]);
                    if ($expanded !== null) {
                        return array_map(static fn (MirrorColumn $c) => $c->name, $expanded);
                    }
                }
                return [$first[0]];
            }
        }
        $raw = $this->singularizeParentTf($entry->tfName);
        return ["{$raw}_id"];
    }

    /** True when the shape's expanded column names already exist among the key columns. */
    private function shapeExpandsIntoKeyCols(string $shape, string $fieldName, array $keyCols): bool
    {
        $resolved = MirrorFieldDecomposer::fromShape($shape, $fieldName);
        if ($resolved === null) {
            return false;
        }
        foreach ($resolved as $col) {
            if (in_array($col->name, $keyCols, true)) {
                return true;
            }
        }
        return false;
    }

    /** FK→ and 1:N child shapes always become fact tables. */
    private function isFactShape(string $shape): bool
    {
        return str_starts_with($shape, 'FK→') || $shape === '1:N child';
    }

    /**
     * T4e: zero-regex singularization of parent tf name.
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
            $targetType = substr($shape, 5);
            return $this->resolveFkTarget($parentTf, $fieldName, $targetType, $parentKeyCols);
        }
        if ($shape === '1:N child') {
            return $this->resolveVectorChild($parentTf, $fieldName, $parentKeyCols);
        }
        // Scalar child table (catalog may declare a nullable scalar as a fact).
        $childTfName = "{$parentTf}_{$fieldName}";
        $resolved = MirrorFieldDecomposer::fromShape($shape, $fieldName);
        if ($resolved === null) {
            return null;
        }
        $columns = $this->makeChildPkCols($parentKeyCols);
        $columns = array_merge($columns, $resolved);
        return $this->makeChildTable($childTfName, $parentKeyCols, $columns, parentTf: $parentTf);
    }

    /**
     * @param list<string> $parentKeyCols
     */
    private function resolveFkTarget(string $parentTf, string $fieldName, string $targetType, array $parentKeyCols): ?MirrorTable
    {
        $childTfName = "{$parentTf}_{$fieldName}";
        $mirror = $this->catalog->tableForTlType($targetType);

        if ($mirror !== null) {
            // T4a/T4b: FK→catalog-parent → the shared catalog table IS the
            // child node, named by the catalog (not `{referrer}_{field}`) and
            // resolved context-free. It stays parent=true with its own key and
            // constructor discriminator — the reference is a row in the shape
            // store, not a fully home-grown union table.
            return $this->resolveTable($mirror->tfName);
        }

        // Non-catalog object, OR catalog parent with an empty base (synthetic-key
        // parents like tf_message_medias are keyed by the referrer context): decompose
        // the ctor union so the fact's own fields live with the fact.
        $tlType = $this->scheme->types()[$targetType] ?? null;
        if ($tlType === null) {
            return null;
        }
        $decomposed = $this->unionDecompose($childTfName, $tlType, $parentKeyCols, $parentTf);

        return $decomposed;
    }

    /**
     * Resolve a 1:N vector child field from the TL scheme.
     *
     * @param list<string> $parentKeyCols
     */
    private function resolveVectorChild(string $parentTf, string $fieldName, array $parentKeyCols): ?MirrorTable
    {
        // Catalog path: the parent is a catalog table — resolve its vector
        // element from the parent's TL type.
        $parentEntry = $this->catalog->table($parentTf);
        $tlType = $this->scheme->types()[$parentEntry->tlType] ?? null;
        if ($tlType === null) {
            return null;
        }
        return $this->resolveVectorFromType($tlType, $parentTf, $fieldName, $parentKeyCols);
    }

    /**
     * Resolve a Vector field's element type into a positioned 1:N child.
     *
     * Object elements union-decompose with a `position` ordinal; scalar
     * elements (Vector<long>, Vector<string>, ...) become a positioned child
     * holding a single `value` column so no element data is dropped.
     *
     * @param list<string> $parentKeyCols
     */
    private function resolveVectorFromType(TlType $parentTlType, string $parentTf, string $fieldName, array $parentKeyCols): ?MirrorTable
    {
        $fieldParam = $this->findParam($parentTlType->constructors(), $fieldName);
        if ($fieldParam === null) {
            return null;
        }
        $elementType = $fieldParam->baseType();
        $childTfName = "{$parentTf}_{$fieldName}";
        $elementTlType = $this->scheme->types()[$elementType] ?? null;
        if ($elementTlType === null) {
            if ($elementType === 'Peer') {
                $columns = $this->makeChildPkCols($parentKeyCols);
                $columns[] = new MirrorColumn('position', MirrorColumnType::TinyInt);
                $columns[] = (new MirrorColumn('value_type', MirrorColumnType::TinyInt, peer: true));
                $columns[] = (new MirrorColumn('value_id', MirrorColumnType::BigInt, peer: true));
                return $this->makeChildTable($childTfName, $parentKeyCols, $columns, positioned: true, parentTf: $parentTf);
            }
            // Scalar vector → positioned child with one `value` column.
            $columns = $this->makeChildPkCols($parentKeyCols);
            $columns[] = new MirrorColumn('position', MirrorColumnType::TinyInt);
            $columns[] = $this->scalarValueColumn($elementType);
            return $this->makeChildTable($childTfName, $parentKeyCols, $columns, positioned: true, parentTf: $parentTf);
        }
        return $this->unionDecompose($childTfName, $elementTlType, $parentKeyCols, $parentTf, positioned: true);
    }

    private function scalarValueColumn(string $elementType): MirrorColumn
    {
        return match ($elementType) {
            'int'     => new MirrorColumn('value', MirrorColumnType::Integer),
            'long'    => new MirrorColumn('value', MirrorColumnType::BigInt),
            'double'  => new MirrorColumn('value', MirrorColumnType::Double),
            'bool', 'True' => new MirrorColumn('value', MirrorColumnType::Boolean),
            'string'  => new MirrorColumn('value', MirrorColumnType::String),
            'bytes'   => new MirrorColumn('value', MirrorColumnType::String, hex: true),
            default   => new MirrorColumn('value', MirrorColumnType::String),
        };
    }

    /**
     * Decompose a TL type's ctor set into a child table.
     *
     * The child collects the UNION of params across all non-empty variants:
     *  - `flags.N?true`/bool params → BOOLEAN NOT NULL DEFAULT FALSE
     *  - scalar params → NOT NULL columns (fed with sentinel defaults by ingest
     *    when the current variant lacks the field)
     *  - Peer params → `{p}_type TINYINT` + `{p}_id BIGINT` column pairs
     *  - object params → nested 1:1 fact table (union-decomposed, or FK reference)
     *  - Vector params → nested 1:N fact table with `position`
     * A real `constructor` TEXT column discriminates the stored variant (>1 ctor).
     * Bare `flags`/`flags2` bitmask params are dropped (never stored).
     *
     * @param list<string> $parentKeyCols
     */
    private function unionDecompose(
        string $childTfName,
        TlType $tlType,
        array $parentKeyCols,
        string $parentTf,
        bool $positioned = false,
    ): ?MirrorTable {
        // The `.tl` type graph is cyclic (RichText/Page/JSONValue recursion,
        // InputUser↔InputPeer request structs, MessageMedia→Poll→PollResults→…).
        // Union decomposition must never re-enter a type already on the current
        // ancestor chain — that path is unbounded in the protocol and no finite
        // row can store it; the branch is dropped (data stays in the parent).
        $typeName = $tlType->name;
        if (in_array($typeName, $this->unionStack, true)) {
            return null;
        }
        // Input-side ctors (`inputPeerUser`, `inputMessageEntityMentionName`,
        // ...) are client request envelopes. Updates never carry them, so they
        // are never mirrored: dropping them removes both phantom tables and the
        // InputUser/InputPeer recursion they pull in.
        $ctors = array_values(array_filter(
            $tlType->constructors(),
            static fn (TlConstructor $c): bool => ! str_starts_with($c->name, 'input'),
        ));
        if ($ctors === []) {
            return null;
        }

        $this->unionStack[] = $typeName;
        $columns = $this->makeChildPkCols($parentKeyCols);
        if ($positioned) {
            $columns[] = new MirrorColumn('position', MirrorColumnType::TinyInt);
        }
        $constructor = count($ctors) > 1 ? 'constructor' : '';
        if ($constructor !== '') {
            $columns[] = new MirrorColumn($constructor, MirrorColumnType::String);
        }

        $colMap  = [];
        $children = [];
        foreach ($columns as $c) {
            $colMap[$c->name] = true;
        }
        foreach ($ctors as $ctor) {
            $this->unionParams($tlType, $childTfName, $ctor, $parentKeyCols, $colMap, $columns, $children);
        }

        $result = $this->makeChildTable($childTfName, $parentKeyCols, $columns, $children, $positioned, $parentTf);
        array_pop($this->unionStack);
        return $result;
    }

    /**
     * Fold one ctor's params into the union column/child sets (deduplicated by name).
     *
     * @param list<string> $parentKeyCols
     * @param array<string,true> $colMap
     * @param list<MirrorColumn> $columns
     * @param list<MirrorTable> $children
     */
    private function unionParams(
        TlType $tlType,
        string $childTfName,
        TlConstructor $ctor,
        array $parentKeyCols,
        array &$colMap,
        array &$columns,
        array &$children,
    ): void {
        foreach ($ctor->params() as $param) {
            if ($this->isBareFlagsMask($param)) {
                continue;
            }
            if ($param->baseType() === 'Peer') {
                $this->foldPeerParam($param, $colMap, $columns);
                continue;
            }
            switch ($param->kind()) {
                case 'true':
                    $this->foldColumn($param->name, 'BOOLEAN NOT NULL DEFAULT FALSE', false, $colMap, $columns);
                    break;
                case 'scalar':
                    $shape = MirrorFieldDecomposer::shapeForParam($param);
                    $this->foldColumn($param->name, $shape, $param->baseType() === 'bytes', $colMap, $columns);
                    break;
                case 'ref':
                    if (in_array($param->baseType(), ['Bool', 'True'], true)) {
                        $this->foldColumn($param->name, 'BOOLEAN NOT NULL DEFAULT FALSE', false, $colMap, $columns);
                        break;
                    }
                    $nested = $this->resolveFkTarget($childTfName, $param->name, $param->baseType(), $parentKeyCols);
                    if ($nested !== null) {
                        $this->mergeChild($nested, $children);
                    }
                    break;
                case 'vector':
                    $nested = $this->resolveVectorFromType($tlType, $childTfName, $param->name, $parentKeyCols);
                    if ($nested !== null) {
                        $this->mergeChild($nested, $children);
                    }
                    break;
                default:
                    break;
            }
        }
    }

    /**
     * Drop bare bitmask params (`flags`, `flags2`, ...) which are `#`-typed.
     */
    private function isBareFlagsMask(TlParam $param): bool
    {
        if ($param->baseType() !== '#') {
            return false;
        }
        $name = $param->name;
        if ($name === 'flags') {
            return true;
        }
        if (str_starts_with($name, 'flags') && strlen($name) > 5) {
            $rest = substr($name, 5);
            if (ctype_digit($rest)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Append a nested child table, deduplicating by tfName.
     *
     * A union type can expose the same field name both as a bare ref (one ctor)
     * and as a Vector (another ctor) — e.g. MessageMedia.extended_media is a
     * single MessageExtendedMedia on messageMediaInvoice and a
     * Vector<MessageExtendedMedia> on messageMediaPaidMedia. The vector form
     * carries the `position` ordinal and must win over the 1:1 form, so a
     * positioned replacement supersedes an existing non-positioned sibling.
     *
     * @param list<MirrorTable> $children
     * @param-out list<MirrorTable> $children
     */
    private function mergeChild(MirrorTable $nested, array &$children): void
    {
        foreach ($children as $i => $child) {
            if ($child->tfName !== $nested->tfName) {
                continue;
            }
            if ($nested->positioned && ! $child->positioned) {
                $children[$i] = $nested;
            }
            return;
        }
        $children[] = $nested;
    }

    /**
     * @param array<string,true> $colMap
     * @param list<MirrorColumn> $columns
     * @param-out array<string,true> $colMap
     * @param-out list<MirrorColumn> $columns
     */
    private function foldPeerParam(TlParam $param, array &$colMap, array &$columns): void
    {
        $type = new MirrorColumn("{$param->name}_type", MirrorColumnType::TinyInt, peer: true);
        $id   = new MirrorColumn("{$param->name}_id", MirrorColumnType::BigInt, peer: true);
        foreach ([$type, $id] as $col) {
            if (! isset($colMap[$col->name])) {
                $colMap[$col->name] = true;
                $columns[] = $col;
            }
        }
    }

    /**
     * @param array<string,true> $colMap
     * @param list<MirrorColumn> $columns
     * @param-out array<string,true> $colMap
     * @param-out list<MirrorColumn> $columns
     */
    private function foldColumn(string $name, string $shape, bool $hex, array &$colMap, array &$columns): void
    {
        if (isset($colMap[$name])) {
            return;
        }
        $resolved = MirrorFieldDecomposer::fromShape($shape, $name, $hex);
        if ($resolved === null) {
            return;
        }
        $colMap[$name] = true;
        $columns = array_merge($columns, $resolved);
    }

    /**
     * SQL type for a key column: follows the base shape when the entry declares
     * the column, else TinyInt for `*_type` peer discriminators, else BigInt.
     */
    private function keyType(MirrorTableEntry $entry, string $col): MirrorColumnType
    {
        foreach ($entry->base as [$name, $shape]) {
            if ($name === $col) {
                $resolved = MirrorFieldDecomposer::fromShape($shape, $col);
                if ($resolved !== null && $resolved !== []) {
                    return $resolved[0]->type;
                }
            }
        }
        if (str_ends_with($col, '_type')) {
            return MirrorColumnType::TinyInt;
        }
        return MirrorColumnType::BigInt;
    }

    /**
     * @param list<string> $parentKeyCols
     * @return list<MirrorColumn>
     */
    private function makeChildPkCols(array $parentKeyCols): array
    {
        $cols = [new MirrorColumn('account_id', MirrorColumnType::BigInt)];
        foreach ($parentKeyCols as $col) {
            $cols[] = new MirrorColumn($col, str_ends_with($col, '_type') ? MirrorColumnType::TinyInt : MirrorColumnType::BigInt);
        }
        return $cols;
    }

    /**
     * @param list<MirrorColumn> $columns
     * @param list<MirrorTable>  $children
     * @param list<string>       $parentKeyCols
     */
    private function makeChildTable(
        string $tfName,
        array $parentKeyCols,
        array $columns,
        array $children = [],
        bool $positioned = false,
        string $parentTf = '',
    ): MirrorTable {
        return new MirrorTable(
            tfName: $tfName,
            tlType: '',
            parent: true,
            positioned: $positioned,
            parentTf: $parentTf,
            keyColumns: $parentKeyCols,
            columns: $columns,
            children: $children,
            constructor: $this->constructorName($columns),
            peerColumns: $this->findPeerCols($columns),
            hexColumns: $this->findHexCols($columns),
            booleanColumns: $this->findBoolCols($columns),
        );
    }

    private function constructorName(array $columns): string
    {
        foreach ($columns as $col) {
            if ($col->name === 'constructor') {
                return 'constructor';
            }
        }
        return '';
    }

    /**
     * Find the param with a given name across all constructors of a type.
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
     * Build base columns for a parent table from its catalog entry.
     *
     * @param list<string> $keyCols
     * @return list<MirrorColumn>
     */
    private function buildBaseColumns(MirrorTableEntry $entry, array $keyCols, string $constructor): array
    {
        $columns = [];
        $columns[] = new MirrorColumn('account_id', MirrorColumnType::BigInt);
        // T4c: peer-key scope marks key columns as peer so findPeerCols finds them.
        $isPeerKey = $entry->base !== [] && $entry->base[0][0] === 'peer';
        foreach ($keyCols as $col) {
            $columns[] = new MirrorColumn($col, $this->keyType($entry, $col), peer: $isPeerKey);
        }
        if ($constructor !== '') {
            $columns[] = new MirrorColumn($constructor, MirrorColumnType::String);
        }
        foreach ($entry->base as [$name, $shape]) {
            if ($this->shapeExpandsIntoKeyCols($shape, $name, $keyCols)) {
                continue;
            }
            if ($this->isFactShape($shape)) {
                continue; // routed to child tables in resolveTable()
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