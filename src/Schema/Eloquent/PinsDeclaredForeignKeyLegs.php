<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Schema;

/**
 * Constrains every LEG of a relation's DECLARED foreign key — the tenant leg
 * included — for every model that extends {@see TfMirrorModel} or
 * {@see TlAnchorModel}.
 *
 * ## WHY THIS EXISTS HERE, AND NOT IN THE GENERATED TREE
 *
 * There are two model layers over these same tables. Both were generated
 * without the tenant leg, which is a cross-tenant data leak, not a style
 * difference (D-026, D-028):
 *
 * | layer | models | relations | pin before this trait |
 * |---|---:|---:|---|
 * | `Teleframe\Mirror\Models` (hand-written) | 195 pin-carrying | 340 | `Mirror\Models\PinsDeclaredForeignKeyLegs` |
 * | `Schema\Generated\Models` (`@generated`) | 265 | 422 | **none** |
 *
 * The generated layer could not simply be given the hand-written trait:
 * `SchemaRegenerator::wipe()` DELETES `src/Schema/Generated/Models` wholesale,
 * so a property that lives only in those files is erased by a documented
 * command. D-028's rule is therefore the reason this file exists in
 * `Schema\Eloquent` and not in `Schema\Generated`: **a security property must
 * not live only in a regenerable tree.** Placing it on the shared hand-written
 * base classes means `ModelGenerator` cannot emit a model without it — the
 * generator's only obligation is to keep emitting `extends TfMirrorModel` /
 * `extends TlAnchorModel`, which `tests/Schema/ModelGeneratorTest.php` asserts.
 *
 * Measured on the generated layer before this trait (all by command):
 * 0 of 265 models carried a pin; 422 of 422 relations constrained no tenant
 * leg; `account_id` appeared in 264 relation-bearing files only as a `$casts`
 * entry or a docblock line, and in **zero** relation bodies
 * (`grep -rh 'account_id' … | grep -cE 'return \$this->(hasMany|belongsTo|where)'` → 0).
 * All 422 relations are single-column `hasMany`/`belongsTo` (211 + 211), keyed on
 * the trailing non-tenant leg — the one column pair that is total only WITHIN an
 * account, which with no account context is total within NOTHING.
 *
 * The generator's docblock defers the tenant leg to `AccountScope`. That
 * deferral is FALSE, and was already recorded as D-027: `AccountScope::apply()`
 * is a no-op unless `AccountContext::current()` is non-null, and
 * `AccountContext::set()` / `::for()` have **zero callers in `src/`**
 * (`grep -rn 'AccountContext::\(set\|for\)' src/` → 0 outside the class itself).
 * So the account context is never established, the global scope never filters,
 * and the deferred leg was never coming.
 *
 * ## WHY A SECOND COPY OF THE DERIVATION, RATHER THAN A CROSS-LAYER IMPORT
 *
 * `MeRezaRezaei\Teleframe\Mirror\Models\PinsDeclaredForeignKeyLegs` already
 * implements this exactly. It is not reused here because the layers do not
 * point that way: before this trait, `src/Schema/` contained **zero** code
 * references to `src/Teleframe/` or `src/Laravel/` (all 30 matches were
 * `src/Schema/skills/*.md` prose), so importing the consumer layer's trait into
 * the engine's base class would introduce the inversion rather than preserve a
 * boundary. Two implementations exist; the duplication is not left to drift,
 * because `tests/Schema/EveryRelationConstrainsItsWholeDeclaredKeyTest.php`
 * scans BOTH layers with ONE predicate over the union of their relations — any
 * leg either copy fails to pin is a failing assertion in that gate.
 *
 * ## WHY IT HOOKS THE THREE SEAMS
 *
 * `newHasOne` / `newHasMany` / `newBelongsTo` are the seams every relation of
 * these three kinds is constructed through, so all 422 relations are covered
 * from one place and a relation added tomorrow inherits the boundary without
 * anyone remembering to write it.
 *
 * For the 195 hand-written mirror models the outcome is unchanged, not doubled:
 * those classes `use` the Mirror trait in their own body, and a trait method in
 * the class beats an inherited one, so `Mirror\Models\PinsDeclaredForeignKeyLegs`
 * remains the live implementation for them. The remaining hand-written models on
 * these base classes (`TfDialog`, `TfUpdate`, `TfReaction`, …) declare **zero**
 * relation methods (measured: 0 across all 18), so the seams are never reached
 * there either. Only the generated layer changes behaviour.
 *
 * ## WHY THE LEG SET IS DERIVED AND NEVER TRANSCRIBED
 *
 * Legs are read from the EXECUTED schema — `getForeignKeys()` on the child
 * table — and paired by POSITION, because a foreign key's `columns` and
 * `foreign_columns` are parallel lists describing the same join. So leg *i* is
 * "child `columns[i]` equals parent `foreign_columns[i]`", whatever they are
 * called. No table list, no column list, no name prefix. `account_id` is pinned
 * because the DECLARED KEY contains it, not because this file names it as the
 * thing to pin.
 *
 * ## EAGER LOADING IS DELIBERATELY UNCHANGED, NOT HALF-FIXED
 *
 * Pinning is applied only when Eloquent is adding constraints. Under
 * `Relation::noConstraints()` — which `Builder::eagerLoadRelations()` wraps every
 * relation construction in — the model handed to the seam is a BLANK
 * `newInstance()`, every attribute read here would be null, and pinning would
 * emit `account_id is null` and turn each eager load into zero rows. So the
 * eager path is left exactly as Eloquent built it.
 *
 * That is safe to leave because no eager path reaches these relations
 * (measured across `src/`: `whereHas` / `orWhereHas` / `load` / `loadMissing` /
 * `withCount` / `has` → 0 each; the only two real `->with(` call sites are
 * `->with('app')` on `Vault\TelegramAccount`, not a mirror or generated model).
 * The known limit is that eager loading of these relations remains un-keyed,
 * because `HasOneOrMany::addEagerConstraints()` matches a single `whereIn` key
 * and Laravel exposes no composite-key eager path. Fixing that is a named
 * follow-up; half-fixing it would trade a silent wrong-row leak for a silent
 * empty result set, which fails with the same silence and is harder to notice.
 *
 * @see \MeRezaRezaei\Teleframe\Mirror\Models\PinsDeclaredForeignKeyLegs — the
 *      hand-written layer's implementation of the same derivation
 */
trait PinsDeclaredForeignKeyLegs
{
    /**
     * The table every tenant FK aims at, and the column it uses.
     *
     * These name only what to LOOK FOR; the leg itself is read back off the
     * executed schema by {@see self::tenantLegName()}. A deployment that moved
     * or renamed either would fall through to "no fallback" rather than pin a
     * wrong column.
     */
    private const TENANT_PARENT_TABLE = 'telegram_accounts';

    private const TENANT_LEG = 'account_id';

    /**
     * The column on the tenant table the tenant FK REFERENCES.
     *
     * Distinct from {@see self::TENANT_LEG}: the mirror writes the Telegram USER
     * id into its own `account_id`, and the constraint points that at
     * `telegram_accounts.user_id` — the vault row's surrogate `id` is NOT the
     * tenant. Naming both matters because `tenantLegName()` has to recognise a
     * real tenant constraint, and only the referenced-column side distinguishes
     * this from any other FK into that table.
     */
    private const TENANT_KEY = 'user_id';

    /**
     * Declared foreign keys per CONNECTION INSTANCE, memoised for the process.
     *
     * Schema introspection is a round trip per relation and relations are built
     * in loops.
     *
     * ## WHY THIS IS A `WeakMap` AND NOT A STRING-KEYED ARRAY
     *
     * This used to be `array<string, …>` keyed by
     * `$connection->getName().'|'.$childTable.'|'.$parentTable.'|'.$foreignKeyColumn`,
     * on the stated reasoning that naming the connection was enough to stop one
     * database's constraints being read for another's. It is not, and the failure
     * it caused is the one this comment exists to prevent.
     *
     * A connection NAME is a label, not an identity, and this repo proves it:
     * `tests/Schema/Mirror/MessageMirrorTest.php:23` and
     * `tests/Ingest/Concerns/RunsMigrations.php:43` BOTH register a connection
     * called `ingest`, both sqlite, both `:memory:` — and they migrate DIFFERENT
     * schemas. Testbench builds a fresh `Connection` per test, so two databases in
     * one process genuinely shared one cache key. The first to be introspected
     * filled that key, and every later database read the first one's answer: a
     * schema with no declared keys yielded `[]`, which silently pins NO legs and
     * re-opens the cross-tenant leak this trait exists to close.
     *
     * The memo is keyed by the `Connection` OBJECT, weakly: `WeakMap` means the
     * entry dies with the connection that produced it, so a per-test in-memory
     * database cannot outlive itself in the cache. `$connection->getName()` is
     * still carried INSIDE each key, purely so a debug dump names the connection;
     * it no longer identifies anything.
     *
     * @var \WeakMap<object, array<string, list<array{columns: list<string>, foreign_columns: list<string>}>>>
     */
    private static ?\WeakMap $declaredKeyLegs = null;

    /**
     * `Relation::$constraints` as a `\ReflectionProperty`, built once per class.
     *
     * Read reflectively rather than through a rebound closure, for two reasons
     * that are both about the property being `protected static` on a class this
     * trait does not extend:
     *
     *  - a closure needs `bindTo(null, Relation::class)` to see it, and that
     *    returns `?Closure`, so the original version carried a `@var
     *    callable(): bool` that contradicted the native type; and
     *  - inside such a closure `static::` is resolved by the ANALYSER against
     *    the trait's host (a Model), which has no such property — the rebinding
     *    is invisible to static analysis even though it is exactly right at
     *    runtime.
     *
     * Reflection reads the same property, at the same moment, with the same
     * *live* value: `Relation::noConstraints()` writes `static::$constraints`
     * on this very property and restores it afterwards, so a cached handle
     * observes the flip and the restore just as the closure did.
     */
    private static ?\ReflectionProperty $constraintsFlag = null;

    /**
     * The memo for `$connection`, created on first use.
     *
     * A static property cannot hold a `WeakMap` literal, so it is initialised here
     * rather than inline.
     *
     * @return \WeakMap<object, array<string, list<array{columns: list<string>, foreign_columns: list<string>}>>>
     */
    private static function declaredKeyLegsMemo(): \WeakMap
    {
        return self::$declaredKeyLegs ??= new \WeakMap;
    }

    /**
     * Whether Eloquent is currently ADDING constraints.
     *
     * `Relation::noConstraints()` flips this off; see the class docblock for why
     * pinning under it would empty every eager load.
     */
    private static function constraintsAreEnabled(): bool
    {
        self::$constraintsFlag ??= new \ReflectionProperty(Relation::class, 'constraints');

        // `(bool)` mirrors the cast the rebound-closure version applied, so the
        // return value is identical on every path.
        return (bool) self::$constraintsFlag->getValue();
    }

    /**
     * The declared foreign keys from `$childTable` into `$parentTable`, narrowed
     * to those naming `$foreignKeyColumn` as one of their legs.
     *
     * Read from the EXECUTED schema. Never from a migration's source text, never
     * from a column-name prefix, never from a hand-written leg list — each of
     * those is a second copy of the constraint that can drift from it silently.
     *
     * @return list<array{columns: list<string>, foreign_columns: list<string>}>
     */
    private static function declaredKeyLegs(string $childTable, string $parentTable, string $foreignKeyColumn): array
    {
        $connection = Schema::getConnection();
        $memo = self::declaredKeyLegsMemo();

        /** @var array<string, list<array{columns: list<string>, foreign_columns: list<string>}>> $byKey */
        $byKey = $memo[$connection] ?? [];
        $cacheKey = $connection->getName().'|'.$childTable.'|'.$parentTable.'|'.$foreignKeyColumn;

        if (array_key_exists($cacheKey, $byKey)) {
            return $byKey[$cacheKey];
        }

        $legs = [];

        try {
            $foreignKeys = $connection->getSchemaBuilder()->getForeignKeys($childTable);
        } catch (\Throwable) {
            // A table that does not exist yet has no declared key, and neither
            // has a model whose table is still being migrated. Returning nothing
            // leaves the relation exactly as Eloquent built it, which is the
            // honest answer: there is no constraint to mirror.
            //
            // NOT MEMOISED, deliberately: "the table is not there" is the one
            // answer here expected to become FALSE later in the same process — a
            // migration runs, the table appears, and the answer must change with
            // it. Caching it is what turned a transient absence into a permanent
            // wrong-row leak (see {@see self::$declaredKeyLegs}).
            return [];
        }

        foreach ($foreignKeys as $foreignKey) {
            if ($foreignKey['foreign_table'] !== $parentTable) {
                continue;
            }

            // `columns` and `foreign_columns` are declared `list<string>` by
            // `Schema\Builder::getForeignKeys()`, so the `(array)` cast and the
            // `array_values()` this used to wrap them in were both no-ops —
            // `array_values()` on a list returns that same list. PHPStan
            // reported the two of them once per class using this trait (388
            // each) because it can see the list-ness statically, while the cast
            // only ever defended against a shape the return type rules out.
            /** @var list<string> $columns */
            $columns = $foreignKey['columns'];
            /** @var list<string> $foreignColumns */
            $foreignColumns = $foreignKey['foreign_columns'];

            if (! in_array($foreignKeyColumn, $columns, true)) {
                continue;
            }

            $legs[] = ['columns' => $columns, 'foreign_columns' => $foreignColumns];
        }

        $byKey[$cacheKey] = $legs;
        $memo[$connection] = $byKey;

        return $legs;
    }

    /**
     * The legs to add for a `hasOne`/`hasMany`, as child column => parent column.
     *
     * `HasOneOrMany` already constrains the one leg the relation was BUILT on, so
     * that leg is dropped and the rest are pinned by value from the parent row.
     *
     * @return array<string, string>
     */
    private static function legsToPinOnChild(
        HasOneOrMany $relation,
        Model $parent,
        string $parentTable,
    ): array {
        $childTable = $relation->getRelated()->getTable();
        $bare = self::bareColumn($relation->getForeignKeyName());

        $pins = [];
        foreach (self::declaredKeyLegs($childTable, $parentTable, $bare) as $declared) {
            foreach ($declared['columns'] as $index => $childColumn) {
                if ($childColumn === $bare) {
                    continue;
                }
                $pins[$childColumn] = $declared['foreign_columns'][$index] ?? $childColumn;
            }
        }

        if ($pins === []) {
            $pins = self::tenantOnlyLeg($childTable, $parentTable);
        }

        return $pins;
    }

    /**
     * The legs to add for a `belongsTo`, as parent column => child column.
     *
     * The direction is INVERTED relative to `HasOneOrMany`: a `belongsTo`
     * constrains the PARENT table, so the value is read off the child row and the
     * predicate is written against the parent's column — which is why these legs
     * are qualified with the parent table's name.
     *
     * @return array<string, string>
     */
    private static function legsToPinOnParent(BelongsTo $relation, Model $child): array
    {
        $parentTable = $relation->getRelated()->getTable();
        $bare = self::bareColumn($relation->getForeignKeyName());

        $pins = [];
        foreach (self::declaredKeyLegs($child->getTable(), $parentTable, $bare) as $declared) {
            foreach ($declared['columns'] as $index => $childColumn) {
                if ($childColumn === $bare) {
                    continue;
                }
                $pins[$declared['foreign_columns'][$index] ?? $childColumn] = $childColumn;
            }
        }

        if ($pins === []) {
            $pins = self::tenantOnlyLeg($child->getTable(), $parentTable);
        }

        return $pins;
    }

    /**
     * The TENANT leg alone, for a relation matching no declared child→parent
     * constraint — the two-hop-shortcut shape, where the declared chain runs
     * through a middle table the relation skips.
     *
     * The tenant boundary is still declared on BOTH tables (each tenant-bearing
     * `tf_*` table carries its own `account_id → telegram_accounts.user_id`), so
     * it is read from there rather than written out here. The two sides are named
     * DIFFERENTLY (`account_id` vs `user_id`), so each leg name is derived
     * independently from its own table's FK; nothing here assumes they match.
     *
     * This fallback can only ever ADD a tenant constraint, never relax one.
     *
     * @return array<string, string>
     */
    private static function tenantOnlyLeg(string $childTable, string $parentTable): array
    {
        $childLeg = self::tenantLegName($childTable);
        $parentLeg = self::tenantLegName($parentTable);

        if ($childLeg === null || $parentLeg === null) {
            return [];
        }

        return [$childLeg => $parentLeg];
    }

    /**
     * The column `$table` carries as its TENANT leg, or null when it declares no
     * tenant constraint at all.
     *
     * Read from the table's own FK into the tenant table, so a table that is not
     * tenant-scoped yields null rather than a guessed column name.
     */
    private static function tenantLegName(string $table): ?string
    {
        foreach (self::declaredKeyLegs($table, self::TENANT_PARENT_TABLE, self::TENANT_LEG) as $declared) {
            foreach ($declared['columns'] as $index => $childColumn) {
                if (($declared['foreign_columns'][$index] ?? null) === self::TENANT_KEY) {
                    return $childColumn;
                }
            }
        }

        return null;
    }

    /** The column with any `table.` qualifier removed — string ops, never regex. */
    private static function bareColumn(string $column): string
    {
        if (! str_contains($column, '.')) {
            return $column;
        }

        return substr($column, (int) strrpos($column, '.') + 1);
    }

    /**
     * Pin the unconstrained legs of the declared key onto a `hasOne`.
     *
     * ## WHY THE TEMPLATES BELOW ARE LARAVEL'S, VERBATIM
     *
     * `HasOne` is a GENERIC class on this Laravel version
     * (`HasOne<TRelatedModel, TDeclaringModel>`) and `newHasOne()` is declared
     * generic by `HasRelationships`. An override that drops the parameters does
     * not merely lose documentation: it makes every call site of `hasOne()`
     * receive `HasOne<Model, …>`, erasing the related-model type the caller
     * asked for. PHPStan caught that as three errors (constructor argument,
     * early return, final return) per class that uses this trait — 582 of the
     * 3299.
     *
     * The two `@param` tags this previously carried (`HasOne<covariant Model,
     * $this> $relation`, and its `HasMany`/`BelongsTo` twins) were the other
     * half: `$relation` is not a parameter of this method at all, so the tag
     * both referenced a nonexistent parameter AND, on `newBelongsTo()`, shadowed
     * the real `$relation` string that `BelongsTo`'s constructor receives —
     * which is why PHPStan also reported the fifth argument being handed a
     * `BelongsTo` where a `string` is expected, and an LSP violation against the
     * inherited `newBelongsTo()`. Both are gone with the tags.
     *
     * @template TRelatedModel of Model
     * @template TDeclaringModel of Model
     *
     * @param  Builder<TRelatedModel>  $query
     * @param  TDeclaringModel  $parent
     * @return HasOne<TRelatedModel, TDeclaringModel>
     */
    protected function newHasOne(Builder $query, Model $parent, $foreignKey, $localKey): HasOne
    {
        $relation = new HasOne($query, $parent, $foreignKey, $localKey);

        if (! self::constraintsAreEnabled()) {
            return $relation;
        }

        foreach (self::legsToPinOnChild($relation, $parent, $parent->getTable()) as $childColumn => $parentColumn) {
            $relation->where($childColumn, $parent->getAttribute($parentColumn));
        }

        return $relation;
    }

    /**
     * Pin the unconstrained legs of the declared key onto a `hasMany`.
     *
     * @template TRelatedModel of Model
     * @template TDeclaringModel of Model
     *
     * @param  Builder<TRelatedModel>  $query
     * @param  TDeclaringModel  $parent
     * @return HasMany<TRelatedModel, TDeclaringModel>
     */
    protected function newHasMany(Builder $query, Model $parent, $foreignKey, $localKey): HasMany
    {
        $relation = new HasMany($query, $parent, $foreignKey, $localKey);

        if (! self::constraintsAreEnabled()) {
            return $relation;
        }

        foreach (self::legsToPinOnChild($relation, $parent, $parent->getTable()) as $childColumn => $parentColumn) {
            $relation->where($childColumn, $parent->getAttribute($parentColumn));
        }

        return $relation;
    }

    /**
     * Pin the unconstrained legs of the declared key onto a `belongsTo`.
     *
     * @template TRelatedModel of Model
     * @template TDeclaringModel of Model
     *
     * @param  Builder<TRelatedModel>  $query
     * @param  TDeclaringModel  $child
     * @return BelongsTo<TRelatedModel, TDeclaringModel>
     */
    protected function newBelongsTo(Builder $query, Model $child, $foreignKey, $ownerKey, $relation): BelongsTo
    {
        $belongsTo = new BelongsTo($query, $child, $foreignKey, $ownerKey, $relation);

        if (! self::constraintsAreEnabled()) {
            return $belongsTo;
        }

        $parentTable = $belongsTo->getRelated()->getTable();
        foreach (self::legsToPinOnParent($belongsTo, $child) as $parentColumn => $childColumn) {
            $belongsTo->where($parentTable.'.'.$parentColumn, $child->getAttribute($childColumn));
        }

        return $belongsTo;
    }
}
