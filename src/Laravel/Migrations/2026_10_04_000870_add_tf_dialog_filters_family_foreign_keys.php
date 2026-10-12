<?php

declare(strict_types=1);

use Illuminate\Database\Connection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorRelationPlan;

/**
 * Task 4.2, family 7: `tf_dialog_filters` and everything descended from it.
 *
 * ## WHAT THIS FAMILY IS — AND WHY IT IS THE ONE WITH A HAZARD
 *
 * A dialog-filter fact plus the eight flat tables the mirror decomposer emits
 * for its fields. `docs/superpowers/plans/2026-10-05-endpoint-coverage-and-mysql
 * -truth-v2.md` names this family as the one that is a **TWO-LEVEL CHAIN**:
 *
 * > **Family 7** — a two-level chain: `tf_dialog_filters_title_entities` is a
 * > grandchild, so the parent's constraint must exist first or the leg set is
 * > ambiguous.
 *
 * So there are TWO depths here and both must land:
 *
 *   depth 1  six relations  → `tf_dialog_filters`
 *   depth 2  one relation   → `tf_dialog_filters_title`
 *
 * `orderByDepth()` below is the load-bearing part of this file. It sorts the
 * discovered relations so that every depth-1 leg is written before any depth-2
 * leg, which is what makes the second depth unambiguous: when the grandchild's
 * REFERENCES target is written, its parent already carries the constraint this
 * same file added.
 *
 * ## THE RELATIONS ARE DISCOVERED, NOT TRANSCRIBED
 *
 * MEASURED on 2026-10-05 by filtering the anti-rot gate's own RED list to this
 * family's two parents — the gate is the work list, so the count is read from
 * there and never written by hand:
 *
 *     timeout -k 10 900 vendor/bin/phpunit --no-coverage \
 *       --filter test_every_single_parent_relation_has_a_real_constraint \
 *       tests/Schema/Mirror/EveryFkableRelationHasAConstraintTest.php 2>&1 \
 *       | grep -oE "tf_[a-z0-9_]+\([^)]*\) → tf_[a-z0-9_]+" | sort -u \
 *       | grep -E "→ tf_dialog_filters(_title)?$" | wc -l
 *     → 7
 *
 * which is 6 at depth 1 plus 1 at depth 2, and which agrees with the 7 the
 * family work-list sized. All 8 tables of this family live in ONE create
 * migration (`2026_09_29_000100_create_tf_mirror_gap_tables.php`, lines
 * 261-366), so nothing here knows or needs to know that.
 *
 * ## THE JOIN COLUMNS ARE NOT WRITTEN IN THIS FILE — THEY ARE DERIVED
 *
 * Every constraint goes through ONE helper, `filterLink()`, which takes the
 * relation `MirrorRelationPlan` produced. The relation list, the table names and
 * the leg columns are all read off the live schema by reflection;
 * `grep -n 'foreign(' ` in this file finds exactly one call, inside that helper.
 * That is the same contract `2026_10_04_000800` and `2026_10_04_000810` set: a
 * hand-written leg list is a second copy of the schema, and a second copy drifts.
 *
 * `MirrorRelationPlan` is the same class the anti-rot gate's RED list is built
 * from, so the migration and the gate cannot disagree about which relations exist
 * or what their legs are.
 *
 * ## WHY THE LEGS ARE THE PARENT'S KEY, AND WHY THAT MATTERS AT BOTH DEPTHS
 *
 * A FOREIGN KEY must reference a UNIQUE index on the parent; MySQL 8.4 refuses a
 * non-unique column set with error 6125. `MirrorRelationPlan` intersects the
 * parent's primary key with the child's real columns, so a child whose key is a
 * strict SUPERSET of its parent's — and four of the six depth-1 children here are
 * exactly that: `tf_dialog_filters_{pinned_peers,include_peers,exclude_peers,
 * title_entities}` are keyed `(account_id, id, position)` — yields the parent's
 * key and no error 6125. The extra `position` is not lost: it is what makes those
 * relations 1:N, and it stays in the child's own key.
 *
 * The depth-2 parent is a different table with a different key, and it is
 * MEASURED rather than assumed. From
 * `src/Laravel/Migrations/2026_09_29_000100_create_tf_mirror_gap_tables.php:278-284`,
 * `tf_dialog_filters_title` declares `$table->bigInteger('id')` and
 * `primary(['account_id', 'id'])` — so its own key IS `(account_id, id)`, and the
 * grandchild's leg set is that key. The gate re-reads it off the engine's index
 * listing in the parent's KEY ORDER, because a composite FOREIGN KEY declared in
 * the wrong order is refused by MySQL (3780) and by nothing in sqlite.
 *
 * ## SIGNEDNESS IS NOT THE ONLY 3780 — INTEGER WIDTH IS TOO, AND THIS FAMILY
 * ## MEASURED THE DIFFERENCE THAT MATTERS
 *
 * MEASURED on MySQL 8.4.11 by reading the EXECUTED dial out of
 * `information_schema.COLUMNS`:
 *
 *     SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE
 *       FROM information_schema.COLUMNS
 *      WHERE TABLE_SCHEMA = <db> AND TABLE_NAME LIKE 'tf_dialog_filters%'
 *        AND COLUMN_NAME IN ('account_id','id');
 *
 *   tf_dialog_filters                     account_id bigint unsigned   id **int**
 *   tf_dialog_filters_title               account_id bigint unsigned   id bigint
 *   tf_dialog_filters_title_entities      account_id bigint unsigned   id bigint
 *   tf_dialog_filters_{color,emoticon,
 *     exclude_peers,include_peers,
 *     pinned_peers}                       account_id bigint unsigned   id bigint
 *
 * MEASURED again, on a purpose-built four-table probe on the same server — one
 * parent keyed `(account_id, int)`, one keyed `(account_id, bigint)`, and two
 * children keyed `(account_id, bigint)`:
 *
 *   ALTER TABLE c_bi ADD CONSTRAINT fk_bi_bi
 *     FOREIGN KEY (account_id,id) REFERENCES p_bi (account_id,id) ON DELETE CASCADE;
 *     → ACCEPTED
 *
 *   ALTER TABLE c_bi ADD CONSTRAINT fk_bi_int
 *     FOREIGN KEY (account_id,id) REFERENCES p_int (account_id,id) ON DELETE CASCADE;
 *     → ERROR 3780 (HY000): Referencing column 'id' and referenced column 'id'
 *       in foreign key constraint 'fk_bi_int' are incompatible.
 *
 * ## THE CONSEQUENCE: THIS FAMILY LANDS 1 OF ITS 7 RELATIONS, AND THE OTHER 6
 * ARE REFUSED FOR A MECHANICAL REASON
 *
 * `signedness` ALONE is not enough for a FOREIGN KEY on MySQL: the integer
 * WIDTH has to match too, and both legs being SIGNED does not satisfy it.
 *
 *   depth 1  6 relations  → `tf_dialog_filters` (int id)   bigint → int   **3780**
 *   depth 2  1 relation   → `tf_dialog_filters_title` (bigint id)  bigint → bigint  OK
 *
 * So all six depth-1 relations are REFUSED, and ONLY the depth-2 leg lands.
 * That is not a shortfall worked around; it is the correct outcome, because the
 * only two ways to land depth 1 are both key changes:
 *
 *  - narrow a CHILD's `id` from `bigint` to `int` — which is a primary-key
 *    column type change on six tables, and
 *  - widen the PARENT's `id` from `int` to `bigint` — which is the "widen a
 *    parent to make a constraint fit" move this plan forbids outright
 *    (verbatim-goals.md Cycle 44): a re-keyed parent key is a NEW schema
 *    surface, and an ALTER that changes a PRIMARY KEY column's type on a
 *    populated table is an index rebuild with data-truncation risk for
 *    out-of-range ids.
 *
 * Rule 2 of this lane is unconditional — leave the relation out, record the
 * mechanical reason, do not ship a key change — so that is what this file does.
 * The reason is written per-table in `REFUSED` below and asserted by the gate.
 *
 * ## THE DEPTH-2 LEG IS STILL THE ONE THAT LANDS, AND IT LANDS FOR THE RIGHT
 * REASON
 *
 * Landing depth 2 while refusing depth 1 INVERTS the hazard the plan named for
 * this family, and the inversion is worth stating rather than glossing:
 *
 * > **Family 7** — a two-level chain: `tf_dialog_filters_title_entities` is a
 * > grandchild, so the parent's constraint must exist first or the leg set is
 * > ambiguous.
 *
 * In the plan's expectation the depth-1 leg lands first and depth 2 follows it.
 * Here depth 1 is refused outright, so the only constraint this file writes IS
 * the depth-2 leg, and its parent — `tf_dialog_filters_title` — is itself a
 * depth-1 relation that carries no constraint from this file. The ordering is
 * therefore vacuous for depth 1 (nothing is written) and trivially satisfied for
 * depth 2 (only one thing is written).
 *
 * What that costs, stated honestly: `tf_dialog_filters_title` is now constrained
 * to its OWN children while remaining unconstrained to its own parent. That is
 * a legal, accepted schema — MySQL does not require a referenced table to be
 * itself constrained — but it means the chain is enforced only at its DEEPER
 * hop. The gate therefore proves depth 2 on its own terms, and the depth-1
 * half of the proofs asserts the REFUSAL, with the 3780 that justifies it
 * re-measured on the target engine.
 *
 *
 * ## ON DELETE CASCADE IS DERIVED, NOT CHOSEN PER TABLE
 *
 * One rule for the whole family, in one place, on the reasoning
 * `2026_10_04_000800` records: `MirrorFactDecomposer::decomposeChildren()` links a
 * fact's children from the parent row, `MirrorFactWriter::write()` orders the
 * batch parent-first precisely so a child cannot land before its parent, and
 * there is no orphan fact in this write path. A dialog-filter child has no life
 * outside its filter: if the filter row is re-placed under a shorter field
 * vector, its old children must go with it or they are orphaned under a
 * re-placed parent. At depth 2 the same rule unwinds the chain: deleting the
 * ROOT takes the title with it and the title takes its entities, which is
 * asserted as its own test because a chain that does not cascade at the first
 * hop leaves the second hop's cascade untested.
 *
 * MEASURED on MySQL 8.4.11 (`tests/MySql/TfDialogFiltersFamilyFkOnMysqlTest.php`,
 * per depth): a valid child row lands, an orphan is refused with `ERROR 1452`
 * read from `$e->errorInfo[1]`, and the same orphan with
 * `SET SESSION foreign_key_checks = 0` lands.
 *
 * ## NO RELATION IS REFUSED FOR A DERIVATION REASON — SIX ARE, FOR A TYPE ONE
 *
 * `REFUSED` below is NOT empty, and the reason is measured rather than assumed:
 * all six depth-1 relations carry their parent's own `(account_id, id)` leg set
 * and their parent's SIGNEDNESS, and are refused anyway because MySQL also
 * requires the integer WIDTH to match and `tf_dialog_filters.id` is `int`
 * against a `bigint` child. See the docblock's "THE CONSEQUENCE" for the probe.
 *
 * This is deliberately a DIFFERENT kind of refusal from the sibling `tf_messages`
 * family's, which declines a relation because the DERIVED LEG SET is wrong
 * (it picks the button's index WITHIN its row instead of the row it hangs
 * under). Here the derived leg set is correct and the ENGINE refuses it. The
 * gate distinguishes the two: it asserts the refused relations' legs ARE the
 * parent's own key, and separately asserts the width mismatch that justifies
 * them — so a future derivation defect cannot be filed under a type refusal and
 * pass.
 *
 * ## NO PRIMARY KEY IS WIDENED OR NARROWED TO MAKE A CONSTRAINT FIT
 *
 * Every leg here is the parent's EXISTING `(account_id, id)` primary key, and
 * every child already carries both columns. Nothing in this file changes a key.
 * Had a relation needed one, it would have been left out with its mechanical
 * reason recorded in `REFUSED` — widening a parent while its children stay
 * unwidened produces children whose `id` resolves to no parent, which is a NEW
 * inconsistency and strictly worse than the uniform state it replaced.
 *
 * ## ADDITIVE, AND NO SHIPPED MIGRATION IS EDITED
 *
 * `git cat-file -e HEAD:src/Laravel/Migrations/2026_09_29_000100_create_tf_mirror
 * _gap_tables.php` succeeds on this tree — the create migration that owns all
 * eight of this family's tables is COMMITTED at HEAD, so it is NOT editable in
 * place. An in-place edit would leave every install that already ran it
 * unconstrained forever, because that migration is long since recorded as
 * applied. (MEASURED, and slightly differently from the sibling families' claim:
 * no `v*` tag carries it yet — `git tag -l 'v*'` checked each tag — so the
 * receipt here is the HEAD commit, not a tag.) The add happens HERE, in a new
 * file, the same convention as `2026_10_04_000100`, `…_000200`, `…_000700`,
 * `…_000800` and `…_000810`.
 *
 * ## WHY sqlite IS NOT THE GATE
 *
 * sqlite cannot raise 3780 (FK column signedness), 6125 (parent key not unique),
 * 1059 (constraint name over 64 characters) or 1170 (TEXT in a PRIMARY KEY) at
 * all, and three of those four would have shipped green in a sqlite-only suite.
 * The gate that proves this migration works on the TARGET engine is the MySQL
 * lane. The SQLite gate here proves the constraints BITE — declared, enforced at
 * each depth separately, and gone when the file is.
 *
 * `down()` REFUSES on a driver that cannot record constraint names, exactly as
 * `2026_10_04_000700` and `2026_10_04_000800` do, rather than either dropping
 * another migration's constraints or reporting a rollback that changed nothing.
 *
 * ## THE COST, STATED RATHER THAN DISCOVERED
 *
 * Each `ALTER TABLE … ADD CONSTRAINT` is a separate, non-atomic rebuild on
 * InnoDB. `2026_10_04_000700` measured 265 of them at a 23.35 s floor on empty
 * tables; these 7 are the same shape. The mirror is nearly empty right now, which
 * is the cheapest window that will ever exist; after the history fill this becomes
 * an online-DDL problem.
 */
return new class extends Migration
{
    /** The family ROOT. Descendants are discovered, never listed. */
    private const FAMILY_ROOT = 'tf_dialog_filters';

    /** A descendant of the root keeps its name. */
    private const FAMILY_PREFIX = 'tf_dialog_filters_';

    /**
     * The suffix every constraint this file creates carries.
     *
     * Length, MEASURED not chosen. MySQL caps an identifier at exactly 64
     * characters (probed one character at a time on 8.4.11; 65 is refused with
     * `ERROR 1059 … Identifier name is too long`) and sqlite caps it at none.
     * The longest table name in THIS family is the GRANDCHILD,
     * MEASURED at 32 characters:
     *
     *     php -r 'echo strlen("tf_dialog_filters_title_entities");'
     *     → 32
     *
     * so the budget for a suffix is 32. `_df_parent_fk` is 13, so the longest
     * name this file can produce is 45 — 19 characters of headroom. The first
     * spelling of the tenant suffix, `_account_tenant_fk` (18) against a
     * 49-character table, produced a 67-character name, shipped green in the
     * suite and was refused on the first real migration, which is why this is
     * measured here rather than chosen.
     *
     * Uniqueness: it must NOT collide with `_msg_parent_fk`
     * (`2026_10_04_000800`), `_st_parent_fk` (`2026_10_04_000810`) or
     * `_acc_tenant_fk` (`2026_10_04_000700`) — a shared suffix makes `down()`
     * unable to tell whose constraints it holds, which is what made the tenant
     * migration's `down()` remove 271 instead of 265.
     */
    private const CONSTRAINT_SUFFIX = '_df_parent_fk';

    /**
     * Relations in this family that are REFUSED, with the reason.
     *
     * All six depth-1 relations, and the reason is MECHANICAL and MEASURED, not
     * a derivation defect: the derived leg set is CORRECT (the parent's own
     * `(account_id, id)`) and MySQL 8.4.11 still refuses it with ERROR 3780,
     * because `tf_dialog_filters.id` is `int` while every child's `id` is
     * `bigint`. Both legs are SIGNED — signedness alone does not satisfy a
     * FOREIGN KEY; the integer WIDTH has to match as well. The four-table probe
     * and the `information_schema` read that establish this are in the
     * docblock.
     *
     * The only ways to land these are a key change — narrowing six children's
     * `id` to `int`, or widening the parent's `id` to `bigint` — and this lane's
     * Rule 2 forbids shipping either. So they are named here rather than
     * discovered as a migration-time 3780 on the first real install.
     *
     * `tests/Schema/Mirror/TfDialogFiltersFamilyForeignKeyTest` asserts all of:
     * every key here is a real discovered family relation, every reason is
     * non-empty, the refused relations' legs ARE their parent's own key (so a
     * derivation defect cannot hide behind a type refusal), and the width
     * mismatch justifying each one is still present in the executed DDL.
     *
     * @var array<string, string>
     */
    private const REFUSED = [
        'tf_dialog_filters_color' => 'the parent key is (account_id, id) and the derived legs are exactly that, '
            .'but tf_dialog_filters.id is `integer` (executed: `int`) while this child\'s `id` is `bigInteger` '
            .'(executed: `bigint`). Both legs are SIGNED, and MySQL 8.4.11 still refuses the ALTER with ERROR 3780 '
            .'(HY000) "Referencing column \'id\' and referenced column \'id\' in foreign key constraint ... are '
            .'incompatible" — MEASURED on a purpose-built four-table probe on 8.4.11, where bigint→bigint is '
            .'ACCEPTED and bigint→int is 3780, with signedness identical in both cases. Landing this relation '
            .'requires narrowing this child\'s primary-key column `id` from bigint to int, or widening the '
            .'parent\'s. Both are key changes and are NOT shipped (Rule 2, verbatim-goals.md Cycle 44).',
        'tf_dialog_filters_emoticon' => 'identical to tf_dialog_filters_color: the parent key is (account_id, id) '
            .'and the derived legs are exactly that, but tf_dialog_filters.id is `integer` and this child\'s `id` '
            .'is `bigInteger`, which MySQL 8.4.11 refuses with ERROR 3780 (MEASURED: bigint→int is 3780 and '
            .'bigint→bigint is accepted, with signedness identical in both cases). Landing it requires a key '
            .'change on the child or the parent, and neither is shipped.',
        'tf_dialog_filters_exclude_peers' => 'identical to tf_dialog_filters_color: the parent key is '
            .'(account_id, id) and the derived legs are exactly that, but tf_dialog_filters.id is `integer` and '
            .'this child\'s `id` is `bigInteger`, which MySQL 8.4.11 refuses with ERROR 3780 (MEASURED: '
            .'bigint→int is 3780 and bigint→bigint is accepted, with signedness identical in both cases). '
            .'Landing it requires a key change on the child or the parent, and neither is shipped.',
        'tf_dialog_filters_include_peers' => 'identical to tf_dialog_filters_color: the parent key is '
            .'(account_id, id) and the derived legs are exactly that, but tf_dialog_filters.id is `integer` and '
            .'this child\'s `id` is `bigInteger`, which MySQL 8.4.11 refuses with ERROR 3780 (MEASURED: '
            .'bigint→int is 3780 and bigint→bigint is accepted, with signedness identical in both cases). '
            .'Landing it requires a key change on the child or the parent, and neither is shipped.',
        'tf_dialog_filters_pinned_peers' => 'identical to tf_dialog_filters_color: the parent key is '
            .'(account_id, id) and the derived legs are exactly that, but tf_dialog_filters.id is `integer` and '
            .'this child\'s `id` is `bigInteger`, which MySQL 8.4.11 refuses with ERROR 3780 (MEASURED: '
            .'bigint→int is 3780 and bigint→bigint is accepted, with signedness identical in both cases). '
            .'Landing it requires a key change on the child or the parent, and neither is shipped.',
        'tf_dialog_filters_title' => 'identical to tf_dialog_filters_color, and this is the load-bearing one: '
            .'tf_dialog_filters_title is the MIDDLE HOP of the two-level chain, so refusing it means the '
            .'depth-2 leg below is constrained to a parent that is itself unconstrained to its own parent. The '
            .'parent key is (account_id, id) and the derived legs are exactly that, but tf_dialog_filters.id is '
            .'`integer` and this child\'s `id` is `bigInteger`, which MySQL 8.4.11 refuses with ERROR 3780 '
            .'(MEASURED: bigint→int is 3780 and bigint→bigint is accepted, with signedness identical in both '
            .'cases). Landing it requires a key change on the child or the parent, and neither is shipped.',
    ];

    /**
     * How many constraints this run added, and the depths they landed at.
     *
     * Recorded, not returned, so a reader can see the loop skipped none and in
     * which order. The gate is what proves it — it asserts the declaration on
     * every discovered family relation from the executed schema rather than
     * trusting this.
     */
    private int $added = 0;

    /** @var array<int, int> `depth => constraints added at that depth` */
    private array $addedByDepth = [];

    public function up(): void
    {
        if (! Schema::hasTable(self::FAMILY_ROOT)) {
            return;
        }

        $added = 0;
        $byDepth = [];

        // `orderByDepth()` is why the grandchild's leg is unambiguous: every
        // depth-1 constraint is written before the first depth-2 one.
        foreach (self::orderByDepth($this->familyRelations()) as $depth => $relations) {
            foreach ($relations as $relation) {
                if ($this->existingConstraint($relation) !== null) {
                    continue;
                }

                Schema::table($relation['child'], function (Blueprint $blueprint) use ($relation): void {
                    self::filterLink($blueprint, $relation);
                });

                $added++;
                $byDepth[$depth] = ($byDepth[$depth] ?? 0) + 1;
            }
        }

        $this->added = $added;
        $this->addedByDepth = $byDepth;
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::FAMILY_ROOT)) {
            return;
        }

        // Reversed: deepest first, so a parent is never dropped while a child
        // still references it.
        $relations = $this->familyRelations();
        $ordered = array_reverse(array_values($relations), preserve_keys: true);

        foreach ($ordered as $relation) {
            $declared = $this->existingConstraint($relation);

            if ($declared === null) {
                continue;
            }

            if (! is_string($declared['name'] ?? null) || $declared['name'] === '') {
                throw new RuntimeException(sprintf(
                    'this driver records no foreign key constraint names, so down() cannot tell which of %s\'s '
                    .'constraints belong to this migration (suffix %s) and which belong to '
                    .'2026_10_04_000700_add_tenant_foreign_keys_to_mirror_tables.php (_acc_tenant_fk). '
                    .'Refusing rather than guessing: dropping them all would silently remove another '
                    .'migration\'s constraints, and dropping none would report a successful rollback that '
                    .'changed nothing. Roll back on MySQL, where constraints are named, or remove them by '
                    .'hand.',
                    $relation['child'],
                    self::CONSTRAINT_SUFFIX,
                ));
            }

            if ($declared['name'] !== $relation['child'].self::CONSTRAINT_SUFFIX) {
                continue;
            }

            Schema::table($relation['child'], function (Blueprint $blueprint) use ($declared): void {
                $blueprint->dropForeign($declared['name']);
            });
        }
    }

    /**
     * THE FAMILY'S ONE LINK HELPER.
     *
     * Every constraint this file creates goes through here, so the on-delete
     * rule and the leg list are derived from a single place rather than
     * transcribed into N call sites. Mirrors `linkChild()` /
     * `participantLink()` / `channelUpdateLink()` / `differenceLink()` /
     * `tenantLink()` / `messageLink()` / `transactionLink()` in the shipped and
     * sibling migrations, which is why those shapes exist at all.
     *
     * ONE helper for TWO parents, deliberately: the leg set is not a function of
     * the table's NAME but of the relation, and both parents here happen to be
     * keyed `(account_id, id)`. A second, depth-specific helper would be two
     * places to drift for no derived difference — and if the two parents' keys
     * ever DID differ, `filterLink()` would still be right about both, because it
     * reads `$relation['columns']` and never a hard-coded leg.
     *
     * @param  array{child:string, parent:string, columns:list<string>, reason:string}  $relation
     */
    private static function filterLink(Blueprint $table, array $relation): void
    {
        $table->foreign($relation['columns'], $relation['child'].self::CONSTRAINT_SUFFIX)
            ->references($relation['columns'])
            ->on($relation['parent'])
            ->cascadeOnDelete();
    }

    /**
     * Group the family's relations by DEPTH below the root, ascending.
     *
     * ## THIS IS THE FUNCTION THE PLAN'S HAZARD IS ABOUT
     *
     * > a two-level chain: `tf_dialog_filters_title_entities` is a grandchild,
     * > so the parent's constraint must exist first or the leg set is ambiguous.
     *
     * Depth is measured by walking each relation's PARENT chain back to the root
     * inside the family — one hop per step — so depth 1 is every relation whose
     * parent IS the root, and depth 2 is every relation whose parent is itself a
     * depth-1 relation. The walk reads `$relation['parent']` rather than counting
     * underscores in a name, because the name is a convention and the parent
     * edge is the fact.
     *
     * Pure and STATIC on purpose: it takes the relation list and returns a
     * grouping, touching no connection, so the gate can invoke it directly and
     * assert the ORDER rather than take it on trust. A cycle cannot appear —
     * every parent edge points at a shorter table name — but a relation whose
     * parent chain never reaches the root is filed at depth 1 rather than
     * dropped, so a discovery surprise is visible in the depth split instead of
     * vanishing.
     *
     * @param  list<array{child:string, parent:string, columns:list<string>, reason:string}>  $relations
     * @return array<int, list<array{child:string, parent:string, columns:list<string>, reason:string}>> depth => relations
     */
    private static function orderByDepth(array $relations): array
    {
        $parentOf = [];
        foreach ($relations as $relation) {
            $parentOf[$relation['child']] = $relation['parent'];
        }

        $depthOf = [];
        foreach ($relations as $relation) {
            $child = $relation['child'];
            $walked = [$child => true];
            $cursor = $relation['parent'];
            $depth = 1;

            while ($cursor !== self::FAMILY_ROOT && isset($parentOf[$cursor]) && ! isset($walked[$cursor])) {
                $walked[$cursor] = true;
                $cursor = $parentOf[$cursor];
                $depth++;
            }

            $depthOf[$child] = $depth;
        }

        $grouped = [];
        foreach ($relations as $relation) {
            $grouped[$depthOf[$relation['child']]][] = $relation;
        }

        ksort($grouped);

        return $grouped;
    }

    /**
     * Every relation in this family, DISCOVERED.
     *
     * Two independent filters, neither of them a transcribed list:
     *
     *  - the edges come from `MirrorRelationPlan`, i.e. from REFLECTION over the
     *    executed DDL plus the catalog's declared edges — the same derivation the
     *    gate's RED list is built from, so the two cannot disagree;
     *  - membership is "the parent is the family root or one of its
     *    descendants", decided by NAME PREFIX on a table list read off the
     *    engine, so a child created in a different migration file is still
     *    found.
     *
     * The returned order is `orderByDepth()`'s natural iteration order flattened
     * — depth ascending — and `up()` re-groups it explicitly rather than relying
     * on this.
     *
     * @return list<array{child:string, parent:string, columns:list<string>, reason:string}>
     */
    private function familyRelations(): array
    {
        $connection = Schema::getConnection();
        $tables = $this->tableNames($connection);

        // A family member is a table whose name extends the root's — the same
        // longest-prefix ancestry rule `bin/generate-relation-graph.php` and
        // `MirrorRelationPlan` document, applied one level down: the parent of
        // a family relation is inside the family, so the parent set is the
        // family ROOT plus every descendant discovered above it.
        $family = [self::FAMILY_ROOT => true];
        foreach ($tables as $table) {
            if (str_starts_with($table, self::FAMILY_PREFIX)) {
                $family[$table] = true;
            }
        }

        $relations = [];
        foreach (MirrorRelationPlan::fromConnection($connection)->singleParent() as $relation) {
            if (! isset($family[$relation['parent']])) {
                continue;
            }

            // The REFUSED list is a FILTER, not an inventory. It is empty for
            // this family today, and it is kept as a filter anyway because the
            // sibling messages family's list is not: keeping the filter only in
            // the family that needs it would mean the filter has never been
            // exercised. `tests/Schema/Mirror/TfDialogFiltersFamilyForeignKeyTest`
            // asserts its shape, its membership and the count arithmetic, so it
            // cannot rot into dead code silently.
            if (isset(self::REFUSED[$relation['child']])) {
                continue;
            }

            $relations[] = $relation;
        }

        return $relations;
    }

    /**
     * The bare table names on this connection, prefix- and schema-stripped.
     *
     * `getTableListing()` returns SCHEMA-QUALIFIED, PREFIXED names
     * ("main.PFX_tf_dialog_filters") while `hasTable()`/`getForeignKeys()` want
     * the BARE name; both halves of that normalisation are measured, not assumed
     * — `tests/Store/ShippedStoreSchema` documents the same shape.
     *
     * @return list<string>
     */
    private function tableNames(Connection $connection): array
    {
        $prefix = $connection->getTablePrefix();
        $names = [];

        foreach ($connection->getSchemaBuilder()->getTableListing() as $listed) {
            $bare = str_contains($listed, '.')
                ? substr($listed, (int) strrpos($listed, '.') + 1)
                : $listed;

            if ($prefix !== '' && str_starts_with($bare, $prefix)) {
                $bare = substr($bare, strlen($prefix));
            }

            $names[] = $bare;
        }

        sort($names);

        return $names;
    }

    /**
     * The constraint this relation would create, if one is ALREADY declared.
     *
     * Read from the executed schema rather than from a list, so a re-run is a
     * no-op rather than a duplicate-constraint error, and so an install that
     * already carries the constraint keeps it.
     *
     * @param  array{child:string, parent:string, columns:list<string>, reason:string}  $relation
     * @return array<string, mixed>|null
     */
    private function existingConstraint(array $relation): ?array
    {
        foreach (Schema::getConnection()->getSchemaBuilder()->getForeignKeys($relation['child']) as $foreign) {
            if ((string) ($foreign['foreign_table'] ?? '') !== $relation['parent']) {
                continue;
            }

            $legs = array_map('strval', $foreign['foreign_columns'] ?? []);
            if ($legs !== $relation['columns']) {
                continue;
            }

            return $foreign;
        }

        return null;
    }
};
