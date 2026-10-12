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
        // The ONE place a key column's SQL type is resolved for a table. The
        // parent's own row reads it off `MirrorKeySpec::parentColumns()` and
        // every child reads it off `MirrorKeySpec::childColumns()`, so a parent
        // and its children cannot disagree about the same key — see
        // `MirrorKeySpec` for the measurement that made the old hardcoded
        // `BigInt` a defect rather than a default.
        $key = new MirrorKeySpec($this->resolveKeyColumns($entry, $keyCols));
        $constructor = count($entry->ctors) > 1 ? 'constructor' : '';
        $columns = $this->buildBaseColumns($entry, $key, $constructor);
        $children = [];

        // Seed the union ancestry with the parent's own type so a child union
        // referencing back to the parent's tlType (or to another catalog parent
        // that is later expanded) terminates instead of recursing infinitely.
        $this->unionStack[] = $entry->tlType;

        // Base entries: scalar/peer shapes become parent columns (buildBaseColumns);
        // FK→ / 1:N fact shapes become child tables. Children entries: EVERY shape
        // (scalar, peer, FK→, 1:N) becomes a child table — row existence = fact
        // existence. Scalar-shaped catalog children (tf_messages.views,
        // via_bot_id, from_rank, ...) must be mirrored, not dropped.
        foreach ($entry->base as [$fieldName, $shape]) {
            if ($keyCols !== [] && $this->shapeExpandsIntoKeyCols($shape, $fieldName, $keyCols)) {
                continue;
            }
            if ($this->isFactShape($shape)) {
                $this->appendChild($tfName, $fieldName, $shape, $key, $children);
            }
        }
        // Children entries declare ANY shape (scalar, peer, FK→, 1:N). The
        // catalog already marked them as facts, so every one becomes a child
        // table — scalars/peers become single-column holds, never dropped.
        foreach ($entry->children as [$fieldName, $shape]) {
            if ($keyCols !== [] && $this->shapeExpandsIntoKeyCols($shape, $fieldName, $keyCols)) {
                continue;
            }
            $this->appendChild($tfName, $fieldName, $shape, $key, $children);
        }

        array_pop($this->unionStack);

        $table = new MirrorTable(
            tfName: $tfName,
            tlType: $entry->tlType,
            parent: true,
            positioned: false,
            parentTf: '',
            keyColumns: $key->names(),
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
     * The FIRST base column decides the natural key (mirrors the catalog's own
     * invariant: base[0] is the table's identity):
     *  1. base[0] = 'id'     → ['id'] (tf_users, tf_chats, tf_todo_items, ...)
     *  2. base[0] = 'peer'   → the expanded peer discriminator columns
     *     (peer_type/peer_id — tf_dialogs, tf_folders, tf_saved_dialogs).
     *  3. base[0] ends in '_id' → the entry's natural key, used directly
     *     (tf_attach_menu_bots.bot_id, tf_quick_replies.shortcut_id,
     *     tf_channel_participants.user_id).
     *  4. Any other base[0]   → no self-identifying natural key (structural
     *     stores like tf_todo_lists whose real identity is contextual):
     *     synthetic `{singular}_id` from the table name. UNCHANGED — see
     *     `deriveNaturalKey()` for the measurement that rejected the obvious
     *     "just use base[0]" alternative.
     *  5. Empty base          → THROWS. There is nothing at all to fall back
     *     on, so the historical `{singular}_id` was a column invented from the
     *     TABLE NAME — put in the DDL, filled by no payload, and left to fail
     *     (or land on a default) far downstream of the catalog edit that caused
     *     it. MEASURED on the shipped catalog 2026-10-03, RE-MEASURED
     *     2026-10-04: reached by NO shipped entry. It was TWO empty-base entries
     *     then (tf_bot_infos, tf_message_medias); the widening gave tf_message_medias
     *     a `peer_id` base, so ONE now, and it declares a `key` — the throw is armed.
     *
     * ## WHAT THE HEURISTIC STILL CANNOT SEE, AND `scoped_by` EXISTS FOR IT
     *
     * Rule 1 trusts the NAME `id` and never asks what scope Telegram scopes it
     * to. Telegram numbers messages PER CHAT, so `(account_id, id)` is not an
     * identity — and no rule here can recover the peer from a column name. A
     * DECLARED `scoped_by` sits ABOVE all of them, because being able to see
     * the scope is precisely what the heuristic cannot do.
     *
     * @throws MirrorSchemaException
     *                               when the entry has no discoverable identity and declares none.
     */
    private function deriveKeyColumns(MirrorTableEntry $entry): array
    {
        // An explicit `key` in the catalog wins outright (Cycle 31, second
        // finding). The catalog's own invariant calls `base[0]` the table's
        // identity regardless of its NAME, so a curated table keyed on a real
        // but un-named identity (`link`, `title`) cannot be recovered by the
        // name heuristic below — it would fall through to a name the entry does
        // not even declare, a column the payload can never fill.
        if ($entry->key !== null) {
            // A DECLARED scope prefixes an EXPRESSED key exactly as it prefixes
            // a derived one, and this arm is where the two were disconnected.
            //
            // MEASURED 2026-10-04, before this arm existed: `tf_message_medias`
            // declared `key: ["message_media_id"]` + `scoped_by: ["peer_type",
            // "peer_id"]`, and the scope was silently DROPPED — `keyColumns`
            // came back `["message_media_id"]` while the shipped DDL and every
            // sibling media table (`tf_messages`, `tf_messages_media`) key
            // `(account_id, peer_type, peer_id, …)`. A declaration that reads as
            // scoped and resolves unscoped is the worst of both: the catalog
            // says the scope is there, so nothing reports its absence, and the
            // row is keyed inside a scope it never had.
            //
            // WHY IT MUST BE A PREFIX AND NOT A REPLACEMENT: `key` names the
            // identity WITHIN the scope — for `tf_message_medias` the enclosing
            // message's `id`, because `MessageMedia` has no union-wide identity
            // of its own to name instead. CORRECTED 2026-10-04: this comment
            // previously claimed "all 19 `messageMedia*` constructors in
            // `api_full.tl:124-142` have no `id` parameter", which was FALSE.
            // Exactly one carries a bare `id` (`messageMediaStory`,
            // `api_full.tl:137`), and that `id` is a STORY id — a different
            // namespace, on 1 of 19 constructors — so it is not a fallback for
            // `message_media_id`, which is why the conclusion is unchanged.
            // The two declarations answer different questions — "which id" and
            // "which chat is it numbered in" — so both are kept, scope first,
            // which is also the order `makeChildPkCols()` and every shipped
            // `primary()` already use.
            //
            // The duplication guard is what keeps this an honest merge rather
            // than a silent rewrite: an entry that already spells its scope
            // inside `key` (the `tf_messages` shape, had it carried an explicit
            // `key`) keeps the key it declared, byte for byte.
            if ($entry->scopedBy === null || $this->keyCarriesScope($entry->key, $entry->scopedBy)) {
                return $entry->key;
            }

            return [...$entry->scopedBy, ...$entry->key];
        }

        // A declared enclosing scope PREFIXES the derived key, and it must sit
        // above the name heuristic: the heuristic's whole failure is that it
        // cannot see the scope.
        //
        // The `base === []` arm is REACHABLE, not unreachable — a base with no
        // `base[0]` leaves `deriveNaturalKey()` at its rule-5 THROW, and that
        // throw's remedy ("declare `scoped_by`") is already satisfied, so
        // falling through would produce a message telling the engineer to do
        // something they had done. It cannot arise on the shipped dial (measured
        // 2026-10-03: both empty-base entries, `tf_bot_infos` and
        // `tf_message_medias`, declare an explicit `key`, which wins above) but
        // the path is reachable by construction and must be correct on its own
        // terms. When there is no `base[0]` to derive, the DECLARED SCOPE IS the
        // whole key: nothing is invented, and rule 5's fabrication is not needed
        // because an identity has in fact been stated.
        if ($entry->scopedBy !== null) {
            if ($entry->base === []) {
                // Already a list: `MirrorCatalog` builds `scopedBy` with
                // `array_values()`, so no re-index is needed or wanted.
                return $entry->scopedBy;
            }

            return [...$entry->scopedBy, ...$this->deriveNaturalKey($entry)];
        }

        return $this->deriveNaturalKey($entry);
    }

    /**
     * True when an expressed `key` ALREADY spells the declared scope, so
     * prefixing it would name every scope column twice.
     *
     * Order matters and is the shipped one: a key that leads with the scope
     * columns (`tf_messages`-shaped: `["peer_type","peer_id","id"]`) is taken as
     * already scoped. A key that merely CONTAINS a scope column somewhere
     * further along is not — a scope belongs in front of the identity, and
     * `makeChildPkCols()` copies this list verbatim onto every child, so a
     * scope buried mid-key would propagate a key whose column order does not
     * match any shipped `primary()`.
     *
     * `scopedBy` is `list<string>|null` by declaration, so the comparison is
     * against the list as parsed — no `array_values()` reindex is wanted here,
     * and adding one is a no-op phpstan reports as `arrayValues.list`. What the
     * guard actually protects is a NON-list coming from a caller that bypassed
     * the declaration, and `MirrorCatalog` already guarantees the list shape by
     * building `scopedBy` with `array_values()` (see its own comment on the
     * empty-declaration check).
     *
     * @param  list<string>  $key
     * @param  list<string>  $scopedBy
     */
    private function keyCarriesScope(array $key, array $scopedBy): bool
    {
        return array_slice($key, 0, count($scopedBy)) === $scopedBy;
    }

    /**
     * Rules 1-5: the COLUMN-NAME derivation, with no scope awareness.
     *
     * @return list<string>
     */
    private function deriveNaturalKey(MirrorTableEntry $entry): array
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
            // T5e: a non-generic `*_id` base field (shortcut_id, bot_id, user_id)
            // is the entry's natural key — always a real TL field. Use it directly
            // instead of fabricating `{singular}_id`.
            if (str_ends_with($first[0], '_id')) {
                return [$first[0]];
            }

            // Rule 4: NO self-identifying natural key. The entry's `base[0]` is
            // a structural/contextual field, not an identity — `tf_todo_lists`
            // opens with `title`, `tf_reactions` with `emoticon` — and its real
            // identity is the ENCLOSING message plus a position. The historical
            // derivation synthesised `{singular}_id` here, and that is KEPT, for
            // a measured reason: 2026-10-03, replacing it with the declared
            // `base[0]` was tried and REJECTED against the shipped DDL, because
            // every such column is FILLABLE (they are real base columns) and so
            // it would pass the fillability oracle while being a worse key —
            // `tf_reactions` would collapse `reactionEmpty` and `reactionPaid`
            // onto one `emoticon NOT NULL` row. A loud drop degrades to a clue;
            // a fillable wrong key corrupts silently. The honest fix is a real
            // key, which is Task 2.2 (`tf_todo_lists`) and Task 3.x (the
            // vector-fed union stores) — not a different fabrication.
            $raw = $this->singularizeParentTf($entry->tfName);

            return ["{$raw}_id"];
        }

        // Rule 5: FABRICATE → THROW (2026-10-03).
        //
        // The old return was `["{$this->singularizeParentTf($tfName)}_id"]`, and
        // it is the reason `singularizeParentTf()` is still here: it invented a
        // column no payload can carry, put it in the DDL, and left the row to
        // fail (or, where the DDL has a default, to land on the default) far
        // downstream of the catalog edit that caused it. A table with no
        // discoverable identity and no declaration is a catalog bug, and it is
        // now a loud one — naming the two things that would fix it.
        throw new MirrorSchemaException(
            "{$entry->tfName}: base is empty and no 'key' or 'scoped_by' is declared, so there is no "
            ."identity to derive — the heuristic used to invent '{$this->singularizeParentTf($entry->tfName)}_id' here, "
            .'a column no '.$entry->tlType.' payload carries. Declare "key" (the columns the shipped DDL keys on, '
            .'with "keyFrom" saying where each value comes from) or "scoped_by" (the enclosing scope).'
        );
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
                return substr($raw, 0, -strlen($from)).$to;
            }
        }
        if (str_ends_with($raw, 's') && strlen($raw) > 1) {
            return substr($raw, 0, -1);
        }

        return $raw;
    }

    /**
     * @param  MirrorKeySpec  $key  the PARENT's resolved key, shared by every
     *                              child of this parent (and, through the
     *                              recursion, of their children too)
     */
    private function resolveChildField(string $parentTf, string $fieldName, string $shape, MirrorKeySpec $key): ?MirrorTable
    {
        if (str_starts_with($shape, 'FK→')) {
            $targetType = substr($shape, 5);

            return $this->resolveFkTarget($parentTf, $fieldName, $targetType, $key);
        }
        if ($shape === '1:N child') {
            return $this->resolveVectorChild($parentTf, $fieldName, $key);
        }
        // Scalar child table (catalog may declare a nullable scalar as a fact).
        $childTfName = "{$parentTf}_{$fieldName}";
        $resolved = MirrorFieldDecomposer::fromShape($shape, $fieldName);
        if ($resolved === null) {
            return null;
        }
        $columns = $this->makeChildPkCols($key);
        $columns = array_merge($columns, $resolved);

        return $this->makeChildTable($childTfName, $key, $columns, parentTf: $parentTf);
    }

    /**
     * Resolve a child field and append it to the parent's child list, skipping
     * duplicates (same tfName = same parent+field already materialized).
     *
     * @param  list<MirrorTable>  $children
     *
     * @param-out list<MirrorTable> $children
     */
    private function appendChild(string $parentTf, string $fieldName, string $shape, MirrorKeySpec $key, array &$children): void
    {
        $child = $this->resolveChildField($parentTf, $fieldName, $shape, $key);
        if ($child !== null && ! $this->hasChild($child->tfName, $children)) {
            $children[] = $child;
        }
    }

    private function resolveFkTarget(string $parentTf, string $fieldName, string $targetType, MirrorKeySpec $key): ?MirrorTable
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
        $decomposed = $this->unionDecompose($childTfName, $tlType, $key, $parentTf, allowInputCtor: $this->catalog->has($parentTf));

        return $decomposed;
    }

    /**
     * Resolve a 1:N vector child field from the TL scheme.
     */
    private function resolveVectorChild(string $parentTf, string $fieldName, MirrorKeySpec $key): ?MirrorTable
    {
        // Catalog path: the parent is a catalog table — resolve its vector
        // element from the parent's TL type.
        $parentEntry = $this->catalog->table($parentTf);
        $tlType = $this->scheme->types()[$parentEntry->tlType] ?? null;
        if ($tlType === null) {
            return null;
        }

        return $this->resolveVectorFromType($tlType, $parentTf, $fieldName, $key);
    }

    /**
     * Resolve a Vector field's element type into a positioned 1:N child.
     *
     * Object elements union-decompose with a `position` ordinal; scalar
     * elements (Vector<long>, Vector<string>, ...) become a positioned child
     * holding a single `value` column so no element data is dropped.
     *
     * ## WHY `position` IS INTEGER AND NOT TINYINT
     *
     * `position` is the ORDINAL of an element inside a Telegram vector, so its
     * width is a function of the vector's length, and the shipped DDL already
     * says `integer`. The resolver said `TINYINT`, and MySQL's TINYINT is a
     * SIGNED 8-bit value — so it tops out at 127.
     *
     * That is not a theoretical bound. The vectors carrying a `position` here
     * include `Message.entities` (`Vector<MessageEntity>`) and
     * `Message.restriction_reason` (`Vector<RestrictionReason>`), and a
     * message carrying more than 127 of either is ordinary, not exotic — a
     * long post split into per-word entities crosses it easily. So did the
     * overflow, and it failed in TWO OPPOSITE directions:
     *
     *  - **Non-strict MySQL/InnoDB** — the SILENT one. The ordinal is CLAMPED
     *    to 127, so elements 128..N all land at position 127 and overwrite each
     *    other under the (parent key, position) key. No error, no clue, no
     *    exception: a message with 300 entities mirrors 127 of them and says
     *    nothing about the missing 173.
     *  - **Strict mode** — the LOUD one, and it is worse than a clamp because it
     *    takes the whole write with it. MySQL raises 1264 "Out of range value",
     *    which is NOT in `MirrorFactWriter::VALUE_NOT_STORABLE_MYSQL_CODES`
     *    (`[1265, 1364, 1366, 1406]` — measured at `MirrorFactWriter.php:299`),
     *    so `MirrorFactWriter` does not downgrade it to a `valueClue`: it falls
     *    through to `else { throw $e; }` (`MirrorFactWriter.php:651`) and the
     *    exception propagates out of the fact write. The message row does not
     *    place at all — not "the message row places, its 128th entity does not".
     *
     * The SQLite logic suite cannot see either failure: SQLite has no TINYINT,
     * does not enforce `VARCHAR(n)`, and stores any integer in any declared
     * integer column. That gap is why the divergence survived — the resolver and
     * the DDL disagreed about a column's width and no SQLite run could tell.
     * All three sites that mint a `position` are widened here together, since
     * a resolver that is right on one vector shape and wrong on the other two
     * is the same defect with extra steps.
     *
     * 1264 is deliberately NOT added to the value-fault constant: making a
     * 128-entity message a one-line clue instead of a raised exception is a
     * real policy decision about what a host should do, and it is not this
     * comment's to make. The fix belongs here, at the width.
     */
    private function resolveVectorFromType(TlType $parentTlType, string $parentTf, string $fieldName, MirrorKeySpec $key): ?MirrorTable
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
                $columns = $this->makeChildPkCols($key);
                $columns[] = new MirrorColumn('position', MirrorColumnType::Integer);
                $columns[] = (new MirrorColumn('value_type', MirrorColumnType::TinyInt, peer: true));
                $columns[] = (new MirrorColumn('value_id', MirrorColumnType::BigInt, peer: true));

                return $this->makeChildTable($childTfName, $key, $columns, positioned: true, parentTf: $parentTf);
            }
            // Scalar vector → positioned child with one `value` column.
            $columns = $this->makeChildPkCols($key);
            $columns[] = new MirrorColumn('position', MirrorColumnType::Integer);
            $columns[] = $this->scalarValueColumn($elementType);

            return $this->makeChildTable($childTfName, $key, $columns, positioned: true, parentTf: $parentTf);
        }

        return $this->unionDecompose($childTfName, $elementTlType, $key, $parentTf, positioned: true, allowInputCtor: $this->catalog->has($parentTf));
    }

    private function scalarValueColumn(string $elementType): MirrorColumn
    {
        return match ($elementType) {
            'int' => new MirrorColumn('value', MirrorColumnType::Integer),
            'long' => new MirrorColumn('value', MirrorColumnType::BigInt),
            'double' => new MirrorColumn('value', MirrorColumnType::Double),
            'bool', 'True' => new MirrorColumn('value', MirrorColumnType::Boolean),
            'string' => new MirrorColumn('value', MirrorColumnType::String),
            'bytes' => new MirrorColumn('value', MirrorColumnType::String, hex: true),
            default => new MirrorColumn('value', MirrorColumnType::String),
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
     */
    private function unionDecompose(
        string $childTfName,
        TlType $tlType,
        MirrorKeySpec $key,
        string $parentTf,
        bool $positioned = false,
        bool $allowInputCtor = false,
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
            static fn (TlConstructor $c): bool => $allowInputCtor || ! str_starts_with($c->name, 'input'),
        ));
        if ($ctors === []) {
            return null;
        }

        $this->unionStack[] = $typeName;
        $columns = $this->makeChildPkCols($key);
        if ($positioned) {
            $columns[] = new MirrorColumn('position', MirrorColumnType::Integer);
        }
        $constructor = count($ctors) > 1 ? 'constructor' : '';
        if ($constructor !== '') {
            $columns[] = new MirrorColumn($constructor, MirrorColumnType::String);
        }

        $colMap = [];
        $children = [];
        foreach ($columns as $c) {
            $colMap[$c->name] = true;
        }
        foreach ($ctors as $ctor) {
            $this->unionParams($tlType, $childTfName, $ctor, $key, $colMap, $columns, $children);
        }

        $result = $this->makeChildTable($childTfName, $key, $columns, $children, $positioned, $parentTf);
        array_pop($this->unionStack);

        return $result;
    }

    /**
     * Fold one ctor's params into the union column/child sets (deduplicated by name).
     *
     * @param  array<string,true>  $colMap
     * @param  list<MirrorColumn>  $columns
     * @param  list<MirrorTable>  $children
     */
    private function unionParams(
        TlType $tlType,
        string $childTfName,
        TlConstructor $ctor,
        MirrorKeySpec $key,
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
                    $nested = $this->resolveFkTarget($childTfName, $param->name, $param->baseType(), $key);
                    if ($nested !== null) {
                        $this->mergeChild($nested, $children);
                    }
                    break;
                case 'vector':
                    $nested = $this->resolveVectorFromType($tlType, $childTfName, $param->name, $key);
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
     * @param  list<MirrorTable>  $children
     *
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

    /** True when a child table with the same tfName (same parent + field) already exists. */
    private function hasChild(string $tfName, array $children): bool
    {
        foreach ($children as $child) {
            if ($child->tfName === $tfName) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string,true>  $colMap
     * @param  list<MirrorColumn>  $columns
     *
     * @param-out array<string,true> $colMap
     * @param-out list<MirrorColumn> $columns
     */
    private function foldPeerParam(TlParam $param, array &$colMap, array &$columns): void
    {
        $type = new MirrorColumn("{$param->name}_type", MirrorColumnType::TinyInt, peer: true);
        $id = new MirrorColumn("{$param->name}_id", MirrorColumnType::BigInt, peer: true);
        foreach ([$type, $id] as $col) {
            if (! isset($colMap[$col->name])) {
                $colMap[$col->name] = true;
                $columns[] = $col;
            }
        }
    }

    /**
     * @param  array<string,true>  $colMap
     * @param  list<MirrorColumn>  $columns
     *
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
                // Match BY NAME inside the expansion, never by position: a
                // peer-pair field expands to [peer_type TINYINT, peer_id BIGINT],
                // so taking element 0 types the `peer_id` half as TINYINT. That
                // was latent while a peer pair was only ever a key via
                // `base[0] = 'peer'` (tf_dialogs.peer_id was typed TINYINT here
                // while the shipped DDL says BIGINT); a `scoped_by` naming
                // `peer_id` directly makes the mistype load-bearing.
                foreach ($resolved ?? [] as $column) {
                    if ($column->name === $col) {
                        return $column->type;
                    }
                }
            }
        }
        if (str_ends_with($col, '_type')) {
            return MirrorColumnType::TinyInt;
        }

        return MirrorColumnType::BigInt;
    }

    /**
     * @return list<MirrorColumn>
     */
    private function makeChildPkCols(MirrorKeySpec $key): array
    {
        // The parent's RESOLVED type for every key column, never a type this
        // method decides for itself — see `MirrorKeySpec` for why a hardcoded
        // `BigInt` here collapsed a string key to 0. `account_id` stays the
        // tenant column every `tf_*` table carries and is never a derived key.
        return $key->childColumns();
    }

    /**
     * @param  list<MirrorColumn>  $columns
     * @param  list<MirrorTable>  $children
     */
    private function makeChildTable(
        string $tfName,
        MirrorKeySpec $key,
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
            keyColumns: $key->names(),
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
     * @param  array<string, TlConstructor>  $constructors
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
     * Resolve a catalog entry's key column NAMES to key COLUMNS: the SQL type
     * `keyType()` resolved for each, and whether it is a peer half.
     *
     * This is the ONLY place either fact is decided for a table, and it runs
     * before any column is built — so the parent's own row and every child of
     * that parent (and, through the recursion, of their children) read one
     * answer rather than two that can drift apart. `MirrorKeySpec` carries it.
     *
     * T4c: peer-key scope marks key columns as peer so findPeerCols finds them.
     * A `scoped_by` declaration is peer-keyed whenever the base field it names
     * expands into peer-FLAGGED columns — which is the whole point of declaring
     * it. Without the flag, `MirrorFactDecomposer` skips `fillPeerHalf()` and
     * looks for a payload field literally named `peer_type`, which no
     * constructor carries: tf_messages ships `peer_id` and nothing else, so the
     * declared scope would be an unfillable key.
     *
     * @param  list<string>  $keyCols
     * @return list<MirrorColumn>
     */
    private function resolveKeyColumns(MirrorTableEntry $entry, array $keyCols): array
    {
        $peerCols = $this->peerKeyColumns($entry);
        $key = [];
        foreach ($keyCols as $col) {
            $key[] = new MirrorColumn($col, $this->keyType($entry, $col), peer: isset($peerCols[$col]));
        }

        return $key;
    }

    /**
     * Build base columns for a parent table from its catalog entry.
     *
     * @return list<MirrorColumn>
     */
    private function buildBaseColumns(MirrorTableEntry $entry, MirrorKeySpec $key, string $constructor): array
    {
        $columns = [];
        $columns[] = new MirrorColumn('account_id', MirrorColumnType::BigInt);
        // The key columns come off the SAME resolution every child of this table
        // inherits, flags included — `resolveKeyColumns()` decided both the SQL
        // type and the peer marking, once, for the whole subtree.
        $keyCols = $key->names();
        foreach ($key->parentColumns() as $keyColumn) {
            $columns[] = $keyColumn;
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
            $resolved = MirrorFieldDecomposer::fromShape($shape, $name, $this->isBytesBaseField($entry, $name));
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
     * Which key columns are PEER columns — the flag
     * `MirrorFactDecomposer::fillPeerHalf()` dispatches on.
     *
     * `fillPeerHalf()` reads the value as a NESTED peer (`is_array($value)`,
     * then `PeerShapeTool::normalize()`). Both curated spellings of that field
     * arrive that way — MEASURED, by running the real decomposer against the
     * real scheme on 2026-10-03: with
     * `'peer_id' => ['_' => 'peerChannel', 'channel_id' => -1001234567890]`
     * in the payload, `fillPeerHalf()` resolves BOTH halves of a `peer_id`-named
     * base field to `[3, -1001234567890]` with zero clues, and the same holds
     * for a `peer`-named field. `TLDecoder::decodeValue()` routes any named type
     * that is not a primitive through `decodeObject()`, and `Peer` is a declared
     * constructor type (`peerUser#59511722` / `peerChat#36c6019a` /
     * `peerChannel#a2a5371e`, `src/Schema/schema/sources/api_full.tl:73-75`), so a `peer_id:Peer`
     * field decodes to exactly the nested object `fillPeerHalf()` reads.
     *
     * ## WHY `peer_id` IS FLAGGED NOW, AND WHY IT WAS NOT FLAGGED BEFORE
     *
     * The previous rule flagged only a base field named `peer`, on the measured
     * ground that flagging `peer_id` "broke a row that placed perfectly". That
     * measurement was real, and it is also incomplete, because the flag only
     * ever mattered for a `peer_id` field that was NOT in the key: as an
     * ordinary base column the expansion was already peer-flagged by
     * `MirrorFieldDecomposer`, so `fillPeerHalf()` ran on it anyway and filled
     * it correctly. Nothing ever flagged the KEY path.
     *
     * It matters now because Task 2.2 declared the scope on `tf_messages`
     * (`"scoped_by": ["peer_type","peer_id"]`), which promotes exactly those two
     * columns into the PRIMARY KEY — and a key column that is not peer-flagged
     * goes down `keyValue()` instead, which looks the column name up as a
     * top-level payload field. Measured, unflagged, with the real shipped
     * catalog and the real decomposition of a real `message` payload:
     *
     *   - `scoped_by: ["peer_id"]`       → "column 'peer_id' got an uncastable
     *     value (type array)", `peer_id` null on the row — the raw nested array
     *     landed in a BIGINT key column and the row could not place at all;
     *   - `scoped_by: ["peer_type","peer_id"]` → "keying column 'peer_type'
     *     missing from payload — cannot place the fact".
     *
     * Both are the `tf_stars_subscriptions` regression Task 2.1 measured and
     * reverted, reproduced here on the entry the declaration was actually meant
     * for. With the `peer_id` spelling flagged, the same measurement returns
     * keyColumns `["peer_type","peer_id","id"]`, both halves peer-flagged, both
     * filled by `fillPeerHalf()` to `[3, -1001234567890]`, and ZERO clues — and
     * the key matches the shipped `primary(['account_id','peer_type','peer_id',
     * 'id'])` exactly, so `MirrorResolverKeyMatchesShippedDdlTest` records no
     * divergence and `SCOPE_AWAITING_MIGRATION` stays empty.
     *
     * ## WHY NAMING THE FIELD IS STILL THE RIGHT DISCRIMINATOR
     *
     * The TL scheme cannot do this job: `Message.peer_id` is declared
     * `peer_id:Peer` and `StarsSubscription.peer` is declared `peer:Peer` —
     * identical as far as `baseType()` is concerned — so both spellings must be
     * named explicitly, and `tf_messages` is the only entry in the shipped
     * catalog whose base field is named `peer_id` (measured over all 36
     * entries), which bounds this change to the one entry it is written for.
     *
     * CHILDREN ARE DELIBERATELY NOT FLAGGED, and that is not an oversight:
     * `makeChildPkCols()` builds child key columns unflagged, so a child's
     * `peer_type`/`peer_id` are copied from the parent ROW rather than being
     * re-derived from the child item's payload — which is correct, because a
     * child item (`MessageEntity`, `KeyboardButton`, …) carries no peer of its
     * own and flagging it would emit "peer column 'peer' missing from payload"
     * for every child of every message.
     *
     * @return array<string,true>
     */
    private function peerKeyColumns(MirrorTableEntry $entry): array
    {
        $peerCols = [];
        foreach ($entry->base as [$name, $shape]) {
            if ($name !== 'peer' && $name !== 'peer_id') {
                continue;
            }
            foreach (MirrorFieldDecomposer::fromShape($shape, $name) ?? [] as $column) {
                if ($column->peer) {
                    $peerCols[$column->name] = true;
                }
            }
        }

        return $peerCols;
    }

    /**
     * True when a parent-table base field maps to a TL `bytes` param.
     * Bytes fields are stored as hex-encoded VARCHAR/TEXT (NF5 §8) — the column
     * must carry the hex marker so ingest encodes and the DDL/consumers know.
     */
    private function isBytesBaseField(MirrorTableEntry $entry, string $fieldName): bool
    {
        $tlType = $this->scheme->types()[$entry->tlType] ?? null;
        if ($tlType === null) {
            return false;
        }
        $param = $this->findParam($tlType->constructors(), $fieldName);

        return $param !== null && $param->baseType() === 'bytes';
    }

    /**
     * @param  list<MirrorColumn>  $columns
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
     * @param  list<MirrorColumn>  $columns
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
     * @param  list<MirrorColumn>  $columns
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
