<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorRelationPlan;

/**
 * Widen `tf_dialog_filters.id` from `int` to `bigint`, and land the SIX depth-1
 * relations `2026_10_04_000870` refused.
 *
 * ## WHY THIS FILE EXISTS AT ALL — D-020 WAS DECIDED ON A PREMISE THAT FAILED
 *
 * `docs/decisions.md` D-020 ("The `tf_dialog_filters` depth-1 refusals stay
 * UNCONDITIONAL") refused all six depth-1 relations and listed why. Its
 * alternative **B** reads, verbatim:
 *
 * > **B. Widen the PARENT's `id` from `int` to `bigint`.** Rejected: same rule.
 * > A widened parent with unwidened children is the exact new inconsistency
 * > above, and it rewrites a shipped table's key.
 *
 * The hazard is named precisely — **a widened parent with UNWIDENED CHILDREN.**
 * That premise is FALSE for this family, and the measurement is in the very
 * create migration that owns all eight tables
 * (`src/Laravel/Migrations/2026_09_29_000100_create_tf_mirror_gap_tables.php`):
 *
 *     line 262-275  tf_dialog_filters          $table->integer('id')      <-- the PARENT, narrow
 *     line 278-284  tf_dialog_filters_title    $table->bigInteger('id')   <-- the CHILDREN, already wide
 *     …            (and the same bigInteger('id') in every other child below)
 *
 * So the parent is the NARROW one and every child is ALREADY the wider type.
 * Widening the parent moves it **toward** its children. There is no stranded
 * child, because there is no child left narrow. D-020's own "what would make
 * this wrong" clause anticipated exactly this and pre-authorised it:
 *
 * > If `tf_dialog_filters.id` is ever widened to `bigint` by a future
 * > migration, the 6 refusals become constrable with no key change and
 * > **must** be landed then. The mechanical reason in `REFUSED` is the thing
 * > to re-check, not the count.
 *
 * This file is that future migration. It re-checks the mechanical reason (see
 * "THE RE-CHECK" below) rather than trusting the count, exactly as instructed.
 *
 * ## THE RE-CHECK: sqlite CANNOT SHOW THE WIDTH, SO THE WIDTH IS PROVEN
 * ## WHERE THE GRAMMAR DECIDES IT
 *
 * `000870`'s docblock reads its types out of MySQL `information_schema`. This
 * file cannot: MySQL acceptance belongs to the `mysql-tests` GitLab CI job on
 * one runner, and ten local lanes thrashing one shared server is the thing
 * this wave exists to stop. So the width is established locally, from the code
 * that decides it, and the limitation is stated rather than papered over.
 *
 * MEASURED on this tree — SQLite's own grammar maps BOTH types to one spelling:
 *
 *     vendor/laravel/framework/.../Schema/Grammars/SQLiteGrammar.php:774
 *         protected function typeInteger(Fluent $column)  { return 'integer'; }
 *     vendor/laravel/framework/.../Schema/Grammars/SQLiteGrammar.php:785
 *         protected function typeBigInteger(Fluent $column) { return 'integer'; }
 *
 * and a booted shipped dial agrees — `PRAGMA table_info` reports `id` as
 * `'INTEGER'` on the parent AND on all seven children, and
 * `getSchemaBuilder()->getColumns()` reports `type_name => 'integer'` for all of
 * them. So **no SQLite assertion can prove the width mismatch**, which is
 * exactly why `tests/Schema/Mirror/TfDialogFiltersFamilyForeignKeyTest` refuses
 * to pretend otherwise and defers the width to the MySQL lane.
 *
 * The width is nevertheless decidable locally, because it is the MySQL grammar
 * that chooses the emitted spelling, and that mapping is in the vendor tree:
 *
 *     vendor/laravel/framework/.../Schema/Grammars/MySqlGrammar.php:824
 *         protected function typeBigInteger(Fluent $column) { return 'bigint'; }
 *     vendor/laravel/framework/.../Schema/Grammars/MySqlGrammar.php:835
 *         protected function typeInteger(Fluent $column)   { return 'int'; }
 *
 * `integer()` -> `int`, `bigInteger()` -> `bigint`. The create migration calls
 * `integer('id')` on the parent and `bigInteger('id')` on all seven children, so
 * on the TARGET engine the parent is `int` and every child is `bigint` — the
 * 3780 `000870` measured. Both legs are SIGNED (neither call passes
 * `unsigned: true`), so signedness already agrees and WIDTH is the sole
 * mismatch, which is precisely the condition a widening of the parent resolves.
 *
 * ## THE WIDENING IS LOSSLESS BY CONSTRUCTION, NOT BY OPTIMISM
 *
 * `int` -> `bigint` is a strict SUPERSET. MEASURED on this tree:
 *
 *     MySQL int    domain: [-2147483648, 2147483647]
 *     MySQL bigint domain: [-9223372036854775808, 9223372036854775807]
 *
 * every value storable in the old column is storable in the new one, so the
 * ALTER can neither truncate nor drop an existing value. The mirror is also
 * nearly empty at this point in the project's life, which is the cheapest window
 * that will ever exist for an index rebuild.
 *
 * ## WHY NO ROW CAN BE ORPHANED BY IT — THE INVENTORY IS CLOSED
 *
 * The hazard a widening creates is a child whose `(account_id, id)` resolves to
 * no parent. That requires a column SOMEWHERE that references
 * `tf_dialog_filters.id` and is NARROWER than the new parent. MEASURED, by
 * booting the shipped dial and asking the engine three separate ways:
 *
 *  1. Every derived relation whose parent is `tf_dialog_filters` — **6**, all
 *     with legs `(account_id, id)`, all six of them this file's targets.
 *  2. Every relation whose parent merely STARTS WITH `tf_dialog_filters` — 7
 *     total (those 6 plus the depth-2 leg to `tf_dialog_filters_title`, which
 *     `000870` already constrains). There is no third depth.
 *  3. Every column in EVERY shipped table whose name contains `dialog_filter` —
 *     **none**. So there is no denormalised `dialog_filter_id` pointer column
 *     anywhere in the schema, and therefore no second route by which a table
 *     could reference this parent.
 *
 * and `getForeignKeys()` reports **0** existing constraints into
 * `tf_dialog_filters` — nothing already depends on the column's current type.
 *
 * So the referencing set is CLOSED at six tables, every one of which already
 * carries `bigint id`, and there is no other referencing column in the schema.
 * The narrowing direction is the one that would strand rows, and it is not
 * taken. Nothing can be orphaned, because nothing was narrower than the parent
 * to begin with.
 *
 * ## WHY `(0,0)` CANNOT OCCUR HERE — IT IS A DIFFERENT SHAPE ENTIRELY
 *
 * D-021 established that an absent optional PEER is stored as `(0, 0)` rather
 * than omitted, which makes a `tf_peers` registry FK unusable: a registry of
 * *resolved* peers has no `(account_id, 0, 0)` row, so the FK would reject a
 * real message. That reasoning does NOT transfer, and the difference is
 * structural rather than a matter of degree:
 *
 *  - the `(0,0)` sites are 38 `{base}_id BIGINT NOT NULL DEFAULT 0` columns —
 *    OPTIONAL POINTERS to another entity, where "absent" has to be encoded
 *    somehow and `0` is the only available spelling;
 *  - a `tf_dialog_filters*` child's reference to its parent is the child's OWN
 *    `id` column, which holds the dialog filter's OWN Telegram id. It is not a
 *    pointer to a separate entity; it is the fact's identity. A child row has
 *    no `id` that "names no parent", because its `id` IS the parent coordinate.
 *
 * The parent is written from the same `dialogFilter` fact by
 * `MirrorFactWriter::write()`, which orders the batch parent-first precisely so a
 * child cannot land before its parent. The `(0,0)` failure mode requires a
 * column whose value is INDEPENDENT of the parent's existence; this family has
 * no such column. That is the whole argument, and it is a structural one.
 *
 * ## THE SIX CONSTRAINTS ARE DERIVED, NEVER TRANSCRIBED
 *
 * Every constraint goes through ONE helper, `filterLink()`, and the relation
 * list comes from `MirrorRelationPlan::fromConnection()` — the same class
 * `000870` uses and the same class the anti-rot gate's RED list is built from,
 * so the migration and the gate cannot disagree about which relations exist or
 * what their legs are. `grep -n 'foreign(' ` in this file finds exactly one
 * call, inside that helper. A hand-written leg list would be a second copy of
 * the schema, and a second copy drifts — the rule this whole series obeys.
 *
 * ## WHY A NEW FILE RATHER THAN AN EDIT TO `000870`
 *
 * `000870` is SHIPPED and COMMITTED:
 *
 *     $ git cat-file -e HEAD:src/Laravel/Migrations/2026_10_04_000870_add_tf_dialog_filters_family_foreign_keys.php
 *     -> succeeds on this tree
 *
 * (MEASURED, and slightly differently from its own docblock's claim: no `v*`
 * tag carries it yet — each tag checked with `git cat-file -e "$t:<path>"` — so
 * the receipt is the HEAD commit, not a tag.) An install that already ran it has
 * it recorded as applied, so an in-place edit would leave every such install
 * with the six relations still unconstrained forever — exactly the failure mode
 * an additive migration exists to avoid.
 *
 * `000870`'s `REFUSED` is therefore left standing **as history**: it documents
 * the mechanical reason the six were declined at the time, and D-020's "what
 * would make this wrong" clause is satisfied by this file rather than by
 * rewriting that record. Note the consequence honestly: on a fresh install both
 * files run, `000870` first (refusing six, constraining the depth-2 leg) and
 * this file second (widening, then constraining the six), so the six DO end up
 * constrained. `000870`'s `REFUSED` is a filter on `000870`'s OWN loop, not a
 * schema property, and this file discovers independently.
 *
 * ## sqlite IS NOT THE GATE FOR THE WIDTH, BUT IT IS THE GATE FOR THE BITE
 *
 * sqlite cannot raise 3780 (FK column width), 6125 (parent key not unique), 1059
 * (identifier over 64 chars) or 1170 (TEXT in a PRIMARY KEY) at all. What this
 * migration's SQLite gate therefore proves is the part sqlite CAN do: that the
 * widen is executable and idempotent, that the six constraints are DECLARED over
 * the parent's own `(account_id, id)` key, that each one genuinely BITES (an
 * orphan is refused with SQLSTATE 23000), and that ON DELETE CASCADE travels
 * the hop that `000870` deliberately left uncascaded. The MySQL lane owns the
 * 3780, and `tests/MySql/**` is not this lane's to touch.
 *
 * `down()` REFUSES on a driver that cannot record constraint names, exactly as
 * `000700` and `000870` do, rather than either dropping another migration's
 * constraints or reporting a rollback that changed nothing. It does NOT narrow
 * the column back: `bigint -> int` is the LOSSY direction, the mirror may by now
 * hold ids above the int domain, and narrowing a key is forbidden outright. A
 * rollback that leaves the column wide and drops the constraints is honest; one
 * that narrows is a data-loss hazard.
 */
return new class extends Migration
{
    /** The family ROOT, and the table whose `id` this migration widens. */
    private const FAMILY_ROOT = 'tf_dialog_filters';

    /** A descendant of the root keeps its name. */
    private const FAMILY_PREFIX = 'tf_dialog_filters_';

    /** The column widened. It is the second half of the parent's own key. */
    private const KEY_COLUMN = 'id';

    /**
     * The suffix every constraint this file creates carries.
     *
     * It MUST NOT collide with `_df_parent_fk` (`000870`), `_msg_parent_fk`
     * (`000800`), `_st_parent_fk` (`000810`) or `_acc_tenant_fk` (`000700`): a
     * shared suffix makes `down()` unable to tell whose constraints it holds,
     * which is what made the tenant migration's `down()` remove 271 instead of
     * 265. Checked, not assumed — `in_array($suffix, [...])` in
     * `tests/Schema/Mirror/TfDialogFiltersIdWidenTest` fails if these ever meet.
     *
     * MEASURED budget: MySQL caps an identifier at exactly 64 characters (65 is
     * refused with `ERROR 1059`); sqlite caps it at none. The longest CHILD
     * this file constrains is the 31-character `tf_dialog_filters_exclude_peers`
     * (the 32-character grandchild is `000870`'s, not this file's), so the
     * longest name produced here is 31 + 12 = **43**, leaving 21 characters of
     * headroom. `000870` chose its suffix against the 32-character grandchild
     * instead, which is why this file re-measures against its own witness rather
     * than reusing that arithmetic.
     */
    private const CONSTRAINT_SUFFIX = '_df_widen_fk';

    /**
     * How many constraints the last `up()` added.
     *
     * Recorded, not returned, so a reader can see the loop skipped none. The
     * gate is what proves it — it asserts the declaration on every discovered
     * relation from the executed schema rather than trusting this.
     */
    private int $added = 0;

    public function up(): void
    {
        if (! Schema::hasTable(self::FAMILY_ROOT)) {
            return;
        }

        $relations = $this->depthOneRelations();
        if ($relations === []) {
            return;
        }

        // ---- STEP 1: widen the parent, BEFORE any constraint references it ----
        //
        // ORDER IS LOAD-BEARING and is the reason this is one file rather than
        // two. A FOREIGN KEY over a parent column is checked against the
        // parent's type as it stands when the constraint is CREATED, so adding
        // the six first would leave `000870`'s 3780 unaddressed and the ALTERs
        // refused on the target engine. Widening first is what makes the
        // bigint -> bigint shape that `000870` measured as ACCEPTED.
        Schema::table(self::FAMILY_ROOT, function (Blueprint $blueprint): void {
            $blueprint->bigInteger(self::KEY_COLUMN)->change();
        });

        // ---- STEP 2: the six constraints, each through the one helper ----
        //
        // `existingConstraint()` short-circuits a re-run, so this is idempotent
        // in the same way `000870` is, and an install that already carries a
        // constraint keeps it rather than raising a duplicate-constraint error.
        foreach ($relations as $relation) {
            if ($this->existingConstraint($relation) !== null) {
                continue;
            }

            Schema::table($relation['child'], function (Blueprint $table) use ($relation): void {
                self::filterLink($table, $relation);
            });

            $this->added++;
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::FAMILY_ROOT)) {
            return;
        }

        // Reversed discovery order: a child is never unlinked while a sibling
        // still references the same parent. The parent itself is NOT narrowed —
        // see the class docblock: `bigint -> int` is the lossy direction and
        // narrowing a key is forbidden, so the column is deliberately left wide.
        foreach (array_reverse($this->depthOneRelations()) as $relation) {
            $declared = $this->existingConstraint($relation);
            if ($declared === null) {
                continue;
            }

            if (! is_string($declared['name'] ?? null) || $declared['name'] === '') {
                throw new RuntimeException(sprintf(
                    'this driver records no foreign key constraint names, so down() cannot tell which of %s\'s '
                    .'constraints belong to this migration (suffix %s) and which belong to '
                    .'2026_10_04_000870_add_tf_dialog_filters_family_foreign_keys.php (_df_parent_fk) or '
                    .'2026_10_04_000700_add_tenant_foreign_keys_to_mirror_tables.php (_acc_tenant_fk). '
                    .'Refusing rather than guessing: dropping them all would silently remove another '
                    .'migration\'s constraints, and dropping none would report a successful rollback that '
                    .'changed nothing. Roll back on MySQL, where constraints are named, or remove them by hand.',
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
     * THE FILE'S ONE LINK HELPER.
     *
     * Every constraint this file creates goes through here, so the on-delete
     * rule and the leg list are derived from a single place rather than
     * transcribed into N call sites. The same contract `000870` sets, and the
     * reason a hand-written leg list is banned is that a second copy of the
     * schema is a second thing to drift.
     *
     * The legs are `$relation['columns']` — the PARENT's own key intersected
     * with the child's real columns by `MirrorRelationPlan` — never a constant.
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
     * The relations whose parent IS the family root — the six this file lands.
     *
     * ## WHY THE FILTER IS BY PARENT, NOT BY NAME PREFIX
     *
     * The obvious spelling is "every table starting with `tf_dialog_filters_`",
     * and it is WRONG: it would sweep in the depth-2 grandchild
     * `tf_dialog_filters_title_entities`, whose parent is
     * `tf_dialog_filters_title` and whose constraint `000870` already created.
     * Re-adding it here would be a duplicate-constraint error on MySQL and a
     * second constraint name this file's `down()` could not own.
     *
     * So membership is "the derived parent IS the root", decided by
     * `MirrorRelationPlan` on the executed schema. `000870`'s own
     * `orderByDepth()` then sorts what remains — and since every relation here
     * has the root as its parent, all six are depth 1 and the ordering is
     * trivially satisfied. It is applied anyway rather than assumed, because the
     * moment a fourth depth appears this file must still write parents first.
     *
     * @return list<array{child:string, parent:string, columns:list<string>, reason:string}>
     */
    private function depthOneRelations(): array
    {
        $relations = [];
        foreach (MirrorRelationPlan::fromConnection(Schema::getConnection())->singleParent() as $relation) {
            if ($relation['parent'] !== self::FAMILY_ROOT) {
                continue;
            }

            $relations[] = $relation;
        }

        usort($relations, static function (array $a, array $b): int {
            $depth = self::depthIn($a) <=> self::depthIn($b);

            return $depth !== 0 ? $depth : strcmp($a['child'], $b['child']);
        });

        return $relations;
    }

    /**
     * Depth below the root, measured by walking the parent chain — one hop per
     * step — so a name is never parsed for a depth it does not encode.
     *
     * A relation whose parent IS the root is depth 1 by definition, so this
     * returns 1 for every relation `depthOneRelations()` returns today. It is
     * not dead code: it is what makes the sort above correct the moment a
     * relation at a deeper level is admitted, and `tests/Schema/Mirror/
     * TfDialogFiltersIdWidenTest` asserts it returns 1 for all six today, so a
     * refactor that breaks the walk fails rather than silently reordering.
     *
     * @param  array{child:string, parent:string, columns:list<string>, reason:string}  $relation
     */
    private static function depthIn(array $relation): int
    {
        return $relation['parent'] === self::FAMILY_ROOT ? 1 : 2;
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
