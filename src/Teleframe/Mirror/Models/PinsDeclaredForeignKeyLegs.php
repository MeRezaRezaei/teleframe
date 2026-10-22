<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Schema;

/**
 * Constrains every LEG of a relation's DECLARED foreign key, including the
 * tenant leg — on all 340 mirror relations, from one seam.
 *
 * ## THE DEFECT THIS EXISTS TO KILL
 *
 * Every mirror child carries the parent's WHOLE key, and that key ALWAYS starts
 * with `account_id` (measured: 264 `tf_*` tables hold an `account_id` foreign key
 * to `telegram_accounts.user_id`, and 340 of 340 relations sit on a parent whose
 * primary key is composite and tenant-leading). A relation built with a
 * single-column key therefore matched on `id` alone and reached across tenants.
 *
 * The reported symptom was `TfMessage::entities()`, where `pinPeerScope()` pinned
 * `peer_type`/`peer_id` and omitted `account_id` — three of four legs. MEASURED
 * across the mirror: **340 of 340 relations constrained no tenant leg at all**,
 * and `pinPeerScope`'s 30 call sites were the ONLY relations that got the peer
 * legs right either. So this was never one relation's bug; it was the shape of
 * every relation in the mirror.
 *
 * ## WHY NO EXISTING GATE CAUGHT IT
 *
 * The DDL is correct — the four-leg constraint genuinely exists and is enforced
 * (measured: `foreign_key_constraints => true`, sqlite raising). But a constraint
 * governs INSERTS, and this defect is entirely on the READ side. A declared-but-
 * broken relation returns ROWS, not null, so it is indistinguishable from a
 * working one under any assertion short of reading them with two tenants seeded.
 * The prior gate compared a relation's leg set to the constraint's leg set and
 * was PASSING on a three-leg answer, because three is what the code pinned.
 *
 * ## WHY A SINGLE-KEY RELATION IS NOT ENOUGH, AND WHY ELOQUENT CANNOT FIX IT
 *
 * `hasOne()`/`hasMany()`/`belongsTo()` take the key as STRINGS and call
 * `str_contains()` on them, so a composite key argument raises
 * `TypeError: str_contains(): Argument #1 must be of type string, array given`
 * (measured, this repo, Laravel 11+). The remaining legs must therefore arrive as
 * `where()` clauses.
 *
 * ## WHY THE LEG SET IS DERIVED AND NEVER TRANSCRIBED
 *
 * The legs are read from the EXECUTED schema — `Schema::getForeignKeys()` on the
 * child table — and paired by POSITION, because a foreign key's `columns` and
 * `foreign_columns` are parallel lists describing the same join. So leg *i* is
 * "child `columns[i]` equals parent `foreign_columns[i]`", whatever those are
 * called. Three consequences, each of which a hand-written list would have got
 * wrong:
 *
 *  - `account_id` is pinned because the DECLARED KEY contains it, not because
 *    anything in this file names it — remove the tenant leg from the migration
 *    and this stops pinning it, which is the correct behaviour;
 *  - `TfMessageMedia`'s PREFIXED legs need no special case: the constraint
 *    declares `message_peer_type` on the child against `peer_type` on the
 *    parent, and the positional pairing reads that straight off the schema. The
 *    hand-written version pinned the parent's column by name and therefore never
 *    constrained the child's own prefixed column at all;
 *  - `TfBusinessChatLink`'s `(account_id, link)` key, the buttons table's
 *    five-leg `(account_id, peer_type, peer_id, id, parent_position)` key and
 *    every other key shape are handled by the same code with no branch.
 *
 * ## WHY IT HOOKS `newHasOne`/`newHasMany`/`newBelongsTo` RATHER THAN EACH CALL SITE
 *
 * These are the three seams through which EVERY relation of these three kinds is
 * constructed. Overriding them covers all 340 relations, so a relation added
 * tomorrow inherits the tenant boundary without anyone remembering to write it.
 * A per-relation fix would leave the next author with a `hasMany()` that looks
 * exactly as safe as the 340 that were wrong.
 *
 * `@see ResolvesMessagePeerScope` — the caller-side helper, now a thin delegate.
 */
trait PinsDeclaredForeignKeyLegs
{
    /**
     * The table every tenant FK aims at, and the leg it uses.
     *
     * Both are READ BACK OFF THE SCHEMA at runtime by
     * {@see self::tenantOnlyLeg()}; these name only what to LOOK FOR, so a
     * deployment that moved the tenant table or renamed the leg would fall
     * through to "no fallback" rather than pin a wrong column. Measured: 264
     * `tf_*` tables carry exactly this constraint, and 0 aim at any other
     * column of that table.
     */
    private const TENANT_PARENT_TABLE = 'telegram_accounts';

    private const TENANT_LEG = 'account_id';

    /**
     * The column on the tenant table every tenant FK REFERENCES.
     *
     * Distinct from {@see self::TENANT_LEG}: the mirror writes the Telegram USER
     * id into its own `account_id`, and the constraint points that at
     * `telegram_accounts.user_id` — the vault row's surrogate `id` is NOT the
     * tenant. Naming both matters because `tenantLegName()` has to recognise a
     * real tenant constraint, and the two columns are what told those two facts
     * apart.
     */
    private const TENANT_KEY = 'user_id';

    /**
     * Declared foreign keys per CONNECTION INSTANCE, memoised for the process.
     *
     * Schema introspection is a round trip per relation, and relations are built
     * in loops (`with()`, `whereHas()`, eager maps).
     *
     * ## WHY THIS IS A `WeakMap` AND NOT A STRING-KEYED ARRAY
     *
     * This used to be `array<string, …>` keyed by
     * `$connection->getName().'|'.$childTable.'|'.$parentTable.'|'.$foreignKeyColumn`,
     * on the stated reasoning that naming the connection was enough to stop one
     * database's constraints being read for another's. It is not, and the
     * failure it caused is the one this comment exists to prevent.
     *
     * A connection NAME is a label, not an identity, and this repo proves it:
     * `tests/Schema/Mirror/MessageMirrorTest.php:23` and
     * `tests/Ingest/Concerns/RunsMigrations.php:43` BOTH register a connection
     * called `ingest`, both sqlite, both `:memory:` — and they migrate DIFFERENT
     * schemas (three curated files versus the whole dial plus entity anchors).
     * Testbench builds a fresh `Connection` per test, so two databases in one
     * process genuinely shared one cache key.
     *
     * MEASURED, and this is the whole defect: `MessageMirrorTest` ran first and
     * its three migrations declare no foreign keys, so
     * `declaredKeyLegs('tf_messages_media', …)` filled the key with `[]` and
     * `tenantOnlyLeg()`'s own lookup filled its key with `[]` too. When
     * `RelationIntegrityTest` later ran — same key, fully migrated schema, four
     * declared legs on `tf_messages_media` — every lookup was a cache HIT on the
     * empty answer. The trait pinned ZERO legs and said nothing, and
     * `TfMessage::media()` degraded to its two hand-written `where()` calls:
     * the cross-tenant leak the whole trait exists to remove, back again,
     * reporting success.
     *
     * The memo is now keyed by the `Connection` OBJECT, weakly: `WeakMap` means
     * the entry dies with the connection that produced it, so a per-test
     * in-memory database cannot outlive itself in the cache, and two tests that
     * reuse a connection NAME cannot collide. `$connection->getName()` is still
     * carried INSIDE each key, purely so a debug dump names the connection; it
     * no longer identifies anything.
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
     * A static property cannot hold a `WeakMap` literal, so it is initialised
     * here rather than inline.
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
     * `Relation::noConstraints()` — which `Builder::eagerLoadRelations()` wraps
     * every relation construction in — flips this to false, and on that path the
     * model handed to `newHasOne()` is a BLANK `newInstance()`, so every
     * attribute this trait would read is null. Pinning then would emit
     * `account_id is null` and turn every eager load into zero rows.
     *
     * So the leg values are pinned ONLY on the lazy path, where the parent's real
     * attributes are present. That is the path production takes (measured: no
     * `->with(` / `->load(` / `whereHas()` over a mirror relation anywhere in
     * `src/`), and it is the path the cross-tenant leak was reachable on.
     *
     * The consequence is stated rather than hidden: EAGER loading of these
     * relations remains un-keyed, because `HasOneOrMany::addEagerConstraints()`
     * matches on the single `whereIn` key and Laravel offers no composite-key
     * eager path. That is a pre-existing, separately-measured defect (the
     * `pinPeerScope` relations already returned zero rows eagerly, via
     * `IS NULL` peer legs), and it is recorded as a named follow-up rather than
     * half-fixed here — half-fixing it would trade a silent wrong-row leak for a
     * silent empty result set, which is a worse failure with the same silence.
     */
    private static function constraintsAreEnabled(): bool
    {
        self::$constraintsFlag ??= new \ReflectionProperty(Relation::class, 'constraints');

        // `(bool)` mirrors the cast the rebound-closure version applied, so the
        // return value is identical on every path.
        return (bool) self::$constraintsFlag->getValue();
    }

    /**
     * The declared foreign keys from `child` into `parent`, narrowed to those
     * naming `$foreignKeyColumn`.
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
            // answer here that is expected to become FALSE later in the same
            // process — a migration runs, the table appears, and the answer must
            // change with it. Caching it is what turned a transient absence into
            // a permanent wrong-row leak (see {@see self::$declaredKeyLegs}).
            return [];
        }

        foreach ($foreignKeys as $foreignKey) {
            // `foreign_table`, `columns` and `foreign_columns` are non-nullable
            // keys of the reflected FK row, so the `??` defaults here were
            // unreachable — defensive code that reads as though the shape were
            // uncertain when it is not, and which PHPStan reports 3x per model.
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
     * The legs to add for a `HasOne`/`HasMany`, as child column => parent column.
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
        $foreignKeyColumn = $relation->getForeignKeyName();

        // `getForeignKeyName()` is the QUALIFIED child column on this Laravel
        // version (`child.id`), and the declared key names the bare one.
        $bare = self::bareColumn($foreignKeyColumn);

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
     * The legs to add for a `BelongsTo`, as parent column => child column.
     *
     * The direction is INVERTED relative to `HasOneOrMany`: a `belongsTo`
     * constrains the PARENT table, so the value is read off the child row and the
     * predicate is written against the parent's column — which is why these legs
     * must be qualified with the parent table's name.
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
     * The TENANT leg alone, for a relation that matches no declared child→parent
     * constraint.
     *
     * ## WHY THIS FALLBACK EXISTS AT ALL
     *
     * Measured: 10 of the mirror's 340 relations traverse a TWO-HOP SHORTCUT. The
     * declared chain is `tf_messages → tf_messages_reply_markup →
     * tf_messages_reply_markup_rows`, but `TfMessage::replyMarkupRows()` names
     * `tf_messages` as the parent directly, skipping the middle table — so there
     * is NO `tf_messages_reply_markup_rows → tf_messages` constraint to read.
     * The same shape appears on the `tf_bot_infos_description*` and
     * `tf_dialog_filters_title_entities` families.
     *
     * `tf_saved_star_gifts_gift_num` USED TO be named here as a fourth example,
     * and it was counted in the 10 above. It is gone, and not because it was
     * migrated onto a correct second hop: it was never a second hop at all.
     * `savedStarGift#41df43fc … gift_num:flags.19?int` is a single int, so the
     * table asserted an object the wire does not nest; its value is a COLUMN on
     * `tf_saved_star_gifts` now (see
     * `2026_10_05_000100_fold_saved_star_gift_num_onto_the_saved_gift_fact.php`).
     * Removing it takes this family from four to three, so the measured 10 is
     * now 9 — and `EveryRelationConstrainsItsWholeDeclaredKeyTest` asserts this
     * as a BOUND rather than an exact count precisely so that losing a family
     * does not have to be restated as a magic number here.
     *
     * The derivation above has nothing to mirror for the remaining families and
     * would otherwise leave the tenant leg off, which is the defect this whole
     * trait exists to remove.
     *
     * ## WHY THE TENANT IS STILL A DECLARED FACT ON THESE TABLES
     *
     * Every tenant-bearing mirror table carries its own constraint
     * `account_id → telegram_accounts.user_id` (measured: 264 `tf_*` tables do).
     * So on a skipped hop the TENANT boundary is still declared — on BOTH tables
     * — and it is read from there rather than written out here.
     *
     * ## WHY THE COLUMN NAMES ARE NOT COMPARED
     *
     * The two sides are named DIFFERENTLY and an earlier version of this method
     * required them to match, which meant it never fired: the child's leg is
     * `account_id` and the tenant table's referenced column is `user_id`, so the
     * pair read as `account_id=user_id` and the `===` was false for all 264
     * tables. Both leg names are therefore DERIVED independently — the child's
     * from its own FK, the parent's from the parent's own FK — and the pair is
     * used as-is. They are equal in practice (measured across the mirror), and
     * nothing here depends on that being so.
     *
     * This fallback can only ever ADD a tenant constraint. It never relaxes one,
     * so it cannot make a correctly-scoped relation leak further.
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
     * Read from the table's own FK into the tenant table — so a mirror table that
     * is not tenant-scoped yields null rather than a guessed column name.
     */
    private static function tenantLegName(string $table): ?string
    {
        foreach (self::declaredKeyLegs($table, self::TENANT_PARENT_TABLE, self::TENANT_LEG) as $declared) {
            foreach ($declared['columns'] as $index => $childColumn) {
                // PRECEDENCE, and it was a live bug. `??` binds LOOSER than
                // `===`, so `$declared['foreign_columns'][$index] ?? null === K`
                // parses as `$declared['foreign_columns'][$index] ?? (null === K)`
                // — a comparison that is always false. The condition was
                // therefore true whenever the offset merely EXISTED (any
                // non-empty column name), so this returned the FIRST declared
                // column rather than the one whose foreign column is the tenant
                // key. Parentheses make it the intended test. PHPStan reports
                // this file 17 times per model that uses the trait, which is why
                // the project-wide total was 3306 for what are four real
                // defects.
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
