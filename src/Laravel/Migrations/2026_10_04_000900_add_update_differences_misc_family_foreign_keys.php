<?php

declare(strict_types=1);

use Illuminate\Database\Connection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorRelationPlan;

/**
 * Task 4.2, family 10: the miscellaneous update-differences group —
 * `tf_update_differences`, `tf_updates_pts`, `tf_business_chat_links`,
 * `tf_wallpapers` and `tf_saved_reaction_tags`, plus every descendant of each.
 *
 * ## WHAT THIS FAMILY IS, AND WHY IT IS THE ODD ONE OUT
 *
 * Every other family in this phase is a SINGLE root plus a name prefix
 * (`tf_messages`, `tf_stars_transactions`, `tf_chats`, …). This one is FIVE
 * roots with NO common prefix, and the plan calls it "the most heterogeneous":
 *
 * > family 10 is the most heterogeneous (five different join shapes:
 * > `(account_id, position)`, `(account_id, seq, position)`, `(account_id, link)`,
 * > `(account_id, saved_reaction_tag_id)`, `(account_id, id)`).
 * > **Split it further if the helpers do not unify.**
 *
 * So `FAMILY_ROOTS` below is a LIST rather than a single constant, and family
 * membership is "this table is one of the five roots, or its name extends one of
 * them". Every table list is still read off the engine; only the five root NAMES
 * are declared, which is the minimum a family definition needs and is the same
 * thing the single-root siblings declare.
 *
 * ## THE DERIVED LEG SETS ARE THE PARENTS' OWN PRIMARY KEYS — MEASURED, NOT ASSUMED
 *
 * A FOREIGN KEY must reference a UNIQUE index on the parent. This family is
 * where that is least obvious, because four of the nine relations are keyed on
 * `position` and one on a THREE-column `(account_id, seq, position)`.
 *
 * MEASURED 2026-10-05 on this tree, by walking the executed dial and reading
 * each parent's real PRIMARY index (command and output in
 * `tests/Schema/Mirror/UpdateDifferencesMiscFamilyForeignKeyTest.php`):
 *
 * | parent | PRIMARY key | children | derived legs |
 * |---|---|---:|---|
 * | `tf_update_differences` | `(account_id, position)` | 4 | `(account_id, position)` |
 * | `tf_updates_pts` | `(account_id, seq, position)` | 1 | `(account_id, seq, position)` |
 * | `tf_business_chat_links` | `(account_id, link)` | 2 | `(account_id, link)` |
 * | `tf_wallpapers` | `(account_id, id)` | 1 | `(account_id, id)` |
 * | `tf_saved_reaction_tags` | `(account_id, saved_reaction_tag_id)` | 1 | `(account_id, saved_reaction_tag_id)` |
 *
 * Each leg set is EXACTLY its parent's key — not a subset, not the child's key.
 * That is the property that makes one helper sound here: every one of the nine
 * constraints references a genuinely UNIQUE index, so MySQL cannot refuse any of
 * them with error 6125, and none of them needs a key widened or narrowed.
 *
 * `MirrorRelationPlan` derives this by intersecting the PARENT's key with the
 * CHILD's real columns, so a child whose key is a strict SUPERSET yields the
 * parent's key rather than a non-unique column set.
 *
 * ## WHY THE FOUR `position` RELATIONS WERE BELIEVED TO BE CONSTRAINED, AND WERE NOT
 *
 * `2026_09_14_200031_create_tf_channel_participants_tables.php` already declares
 * exactly these four constraints, inline, through its `differenceLink()` helper.
 * A reader of that file would reasonably conclude this family is done.
 *
 * MEASURED 2026-10-05, walking the dial ONE FILE AT A TIME and probing the
 * executed DDL after each: the constraint is ABSENT the instant that file
 * finishes. It is not dropped later by a subsequent migration — it is never
 * created. Walking `2026_09_14_200031` forward shows:
 *
 * ```
 * 2026_09_14_200031_create_tf_channel_participants_tables.php  fkTargets=[]  ddlHasForeignKeyClause=false
 * 2026_10_04_000700_add_tenant_foreign_keys_to_mirror_tables.php fkTargets=[telegram_accounts] ddlHasForeignKeyClause=true
 * ```
 *
 * The recorded `CREATE TABLE` for `tf_update_differences_date` ends up as:
 *
 * ```sql
 * CREATE TABLE "tf_update_differences_date" ("account_id" integer not null, "position" integer not null,
 *   "date" integer not null,
 *   foreign key("account_id") references "telegram_accounts"("user_id") on delete cascade,
 *   primary key ("account_id", "position"))
 * ```
 *
 * Only the TENANT leg. The four `differenceLink` constraints are simply not in it.
 *
 * ### WHY — MEASURED IN A THREE-CASE REPRODUCTION, NOT INFERRED
 *
 * ```
 * inline ->foreign()->...->name('x')  inside Schema::create()  →  NO foreign key clause
 * inline ->foreign()->...             inside Schema::create()  →  foreign key clause PRESENT
 * ```
 *
 * `Blueprint::foreign()` registers its command under the name `foreign`
 * (`indexCommand('foreign', …)`), and `SQLiteGrammar::compileCreate()` gathers
 * them with `getCommandsByName($blueprint, 'foreign')`, which matches on
 * `$command->name`. Chaining `->name('fk_diff_link_…')` OVERWRITES that attribute
 * with the constraint name, so the filter matches nothing and the clause is
 * silently omitted — no error, no warning.
 *
 * `MySqlGrammar::compileCreateTable()` never emits foreign commands at all, so
 * the same inline declaration is dropped on MySQL too. That is why these four
 * relations were missing on BOTH engines, and why this file is an ADDITION rather
 * than the repair of something a later migration destroyed.
 *
 * `Schema::table()` is unaffected — it dispatches `foreign` by command TYPE, not
 * by that attribute — which is exactly why the constraints in
 * `2026_09_14_299999_create_tf_foreign_keys.php` and `2026_10_04_000800` do exist.
 *
 * The same defect silently disables `participantLink()` and
 * `channelUpdateLink()` in that same file, and `linkChild()`'s siblings wherever
 * a name is chained. That is recorded here because it is a property of the whole
 * dial, but CONSTRAINING those families is not this task's: this file touches
 * only the nine relations listed in `MEASURED_RELATIONS` below.
 *
 * ## WHY ONE LINK HELPER COVERS ALL FIVE SHAPES — THE SPLIT QUESTION, ANSWERED
 *
 * The plan's instruction is conditional: split IF THE HELPERS DO NOT UNIFY. They
 * do unify, and the reason is structural rather than a matter of taste.
 *
 * The helper does not CHOOSE the leg columns. It takes the relation
 * `MirrorRelationPlan` derived — which already carries `columns` — and applies
 * them. So the five shapes are five different SETS passed to one function, not
 * five different call patterns. There is no shape-specific branch to unify, and
 * nothing to coerce: a three-column leg set is written by exactly the same code
 * path as a two-column one, because both arrive as `list<string>`.
 *
 * Splitting into five helpers would produce five byte-identical functions. Worse,
 * it would put the leg lists somewhere a reader could edit them by hand, which is
 * the failure mode the single-helper rule exists to prevent.
 *
 * The claim "one helper, no coercion" is not asserted here — it is PROVEN by the
 * SQLite gate's `test_every_derived_leg_is_the_parent_s_own_key()`, which checks
 * all nine leg sets against the parents' real PRIMARY keys read off the engine,
 * and pins the per-shape breakdown so a shape cannot quietly change.
 *
 * ## THE JOIN COLUMNS ARE NOT WRITTEN IN THIS FILE — THEY ARE DERIVED
 *
 * `grep -n 'foreign(' ` below this line finds exactly ONE call, inside
 * `miscLink()`. The relation list, the table names and the leg columns are all
 * read by reflection off the live schema, from the same class the anti-rot gate
 * derives its RED list from — so the migration and the gate cannot disagree about
 * which relations exist or what their legs are.
 *
 * The candidate helpers the plan names were CHECKED rather than assumed.
 * `differenceLink()` (`2026_09_14_200031`) is hard-wired to one parent and one
 * fixed leg pair, so it cannot express the other four shapes; and it is the very
 * helper whose inline declaration is silently dropped. `factKey()`
 * (`2026_09_14_200040`) builds COLUMNS, not links. Neither is usable here, so
 * this file defines the family's link helper, as `tf_messages` and
 * `tf_stars_transactions` each did for their own.
 *
 * ## ON DELETE CASCADE IS DERIVED, NOT CHOSEN PER TABLE
 *
 * One rule for the whole family, in one place, on the reasoning
 * `2026_10_04_000800_add_tf_messages_family_foreign_keys.php` records in full:
 * `MirrorFactDecomposer::decomposeChildren()` links a fact's children from the
 * parent row, `MirrorFactWriter::write()` orders the batch parent-first so a
 * child cannot land before its parent, and there is no orphan fact in this write
 * path. A child row here has no life outside its parent fact — a re-delivered
 * update with a shorter field vector must take its old children with it, or they
 * are orphaned under a re-placed parent.
 *
 * ## NO RELATION IN THIS FAMILY IS REFUSED — MEASURED, NOT ASSUMED
 *
 * `REFUSED` is EMPTY, and that is a measurement: all 9 relations carry their
 * parent's own PRIMARY key with matching column types and signedness, so none
 * needs an exception. The constant and the filter that reads it are kept anyway,
 * because the sibling `tf_messages` family's list is NOT empty — keeping a filter
 * only in the family that needs one would mean it had never been exercised. The
 * gate asserts the constant is empty, so this claim cannot rot unnoticed.
 *
 * ## NO PRIMARY KEY IS WIDENED OR NARROWED TO MAKE A CONSTRAINT FIT
 *
 * Every leg here is a parent's EXISTING primary key, and every child already
 * carries all of those columns — measured, in the table above. Nothing in this
 * file changes a key. Had a relation needed one it would have been left out and
 * the mechanical reason recorded in `REFUSED`; widening a parent while its
 * children stay unwidened produces children whose `id` resolves to no parent, a
 * NEW inconsistency and strictly worse than the state it replaced.
 *
 * ## ADDITIVE, AND NO SHIPPED MIGRATION IS EDITED
 *
 * `git cat-file -e <tag>:<path>` — the four create migrations these tables live
 * in (`2026_09_14_200030`, `2026_09_14_200031`, `2026_09_14_200050`, and
 * `2026_09_14_299999`) are all in tag v1.3.x and are therefore NOT editable in
 * place; an in-place edit would leave every install that already ran them
 * unconstrained forever. The add happens HERE, in a new file — the same
 * convention as `2026_10_04_000100`, `…_000200`, `…_000700` and `…_000800`.
 *
 * In particular the `differenceLink()` defect above is deliberately NOT fixed at
 * source: that file is shipped, so correcting its inline declarations would not
 * reach any install that already ran it. Adding the constraints here reaches all
 * of them.
 *
 * ## WHY sqlite IS NOT THE GATE
 *
 * sqlite cannot raise 3780 (FK column signedness), 6125 (parent key not unique),
 * 1059 (constraint name over 64 characters) or 1170 (TEXT in a PRIMARY KEY) at
 * all, and this family is where three of those four hide: five parents of four
 * different integer widths, nine constraints, five shapes. The gate that proves
 * this migration works on the TARGET engine is the MySQL lane
 * (`tests/MySql/UpdateDifferencesMiscFamilyFkOnMysqlTest.php`), not this file's
 * own sqlite exercise. The sqlite gate proves the constraints BITE — declared,
 * enforced, and gone when the file is.
 *
 * ## THE COST, STATED RATHER THAN DISCOVERED
 *
 * Each `ALTER TABLE … ADD CONSTRAINT` is a separate, non-atomic rebuild on
 * InnoDB. `2026_10_04_000700` measured 265 of them at a 23.35 s floor on empty
 * tables; these 9 are the same shape, and they are cheap precisely because the
 * mirror is nearly empty right now — the cheapest window that will ever exist.
 */
return new class extends Migration
{
    /**
     * The family's FIVE ROOTS — the only table names in this file.
     *
     * A list, not a single constant, because unlike every sibling family these
     * roots share no common prefix and there is no prefix that contains them all.
     * Naming a family is the one thing a family definition must do; everything
     * else below is discovered from the engine.
     *
     * MEASURED 2026-10-05 by reading the parents out of the anti-rot gate's own
     * RED list:
     *
     *   timeout -k 10 900 vendor/bin/phpunit --no-coverage \
     *     --filter test_every_single_parent_relation_has_a_real_constraint \
     *     tests/Schema/Mirror/EveryFkableRelationHasAConstraintTest.php 2>&1 \
     *     | grep -oE "tf_[a-z0-9_]+\([^)]*\) → tf_[a-z0-9_]+" | sort -u \
     *     | grep -E "→ tf_update_differences$|→ tf_updates_pts$|→ tf_business_chat_links$|→ tf_wallpapers$|→ tf_saved_reaction_tags$"
     *   → 9
     *
     * @var list<string>
     */
    private const FAMILY_ROOTS = [
        'tf_update_differences',
        'tf_updates_pts',
        'tf_business_chat_links',
        'tf_wallpapers',
        'tf_saved_reaction_tags',
    ];

    /**
     * The number of single-parent relations MEASURED in this family, and the
     * number this migration therefore constrains.
     *
     * Both are 9, from the command above, on this tree at the date in the
     * docblock. Recorded here so a reader can see the loop skipped none — the
     * GATE is what proves it, asserting the declaration on every discovered
     * relation from the executed schema rather than trusting this.
     *
     * Never edit this to reach a green run: re-measure with the command in
     * `FAMILY_ROOTS` and bump it, the gate's constant and the gate's shape
     * breakdown together.
     */
    private const MEASURED_RELATIONS = 9;

    /**
     * The suffix every constraint this file creates carries.
     *
     * Length, MEASURED not chosen. MySQL caps an identifier at exactly 64
     * characters (probed on 8.4.11; 65 is refused with `ERROR 1059 … Identifier
     * name is too long`) and sqlite caps it at none, so a name that is too long
     * passes every sqlite test and fails on the first real ALTER — which is how
     * the tenant migration's first spelling shipped green and was refused.
     *
     * The longest table in THIS family is `tf_business_chat_links_entities`,
     * MEASURED at 31 characters (`php -r 'echo strlen("tf_business_chat_links_entities");'`),
     * so the budget for a suffix is 33. `_udiff_parent_fk` is 15 and the longest
     * name this file can produce is 46.
     *
     * Uniqueness: it must NOT collide with `_msg_parent_fk`
     * (`2026_10_04_000800`), `_st_parent_fk` (`…_000810`), `_usr_parent_fk`
     * (`…_000820`), `_chat_parent_fk` (`…_000880`), `_stars_parent_fk`
     * (`…_000890`) or `_acc_tenant_fk` (`2026_10_04_000700`) — MEASURED on the
     * tenant migration: a shared suffix made its `down()` unable to tell whose
     * constraints it held and it removed 271 instead of 265.
     */
    private const CONSTRAINT_SUFFIX = '_udiff_parent_fk';

    /**
     * Relations in this family that are REFUSED, with the reason.
     *
     * EMPTY, and that is a measurement rather than an omission: all 9 relations
     * carry their parent's own PRIMARY key, with matching column types and
     * signedness, so none of them needs an exception. See the docblock's "NO
     * RELATION IN THIS FAMILY IS REFUSED".
     *
     * A refusal is a NAMED exception, never a silent skip: an unnamed skip is
     * indistinguishable from a bug in the discovery. The filter below is kept
     * even though it currently filters nothing, because the sibling `tf_messages`
     * family's list is NOT empty and a filter only ever added for the family
     * that needs one is a filter that has never been exercised.
     * `tests/Schema/Mirror/UpdateDifferencesMiscFamilyForeignKeyTest` asserts
     * this constant is empty, so it cannot rot into an unexamined claim.
     *
     * @var array<string, string>
     */
    private const REFUSED = [];

    /**
     * How many constraints this run added.
     *
     * Recorded, not returned, so a reader can see the loop skipped none.
     */
    private int $added = 0;

    public function up(): void
    {
        $relations = $this->familyRelations();
        if ($relations === []) {
            return;
        }

        $added = 0;
        foreach ($relations as $relation) {
            if ($this->existingConstraint($relation) !== null) {
                continue;
            }

            Schema::table($relation['child'], function (Blueprint $blueprint) use ($relation): void {
                self::miscLink($blueprint, $relation);
            });
            $added++;
        }

        $this->added = $added;
    }

    public function down(): void
    {
        foreach ($this->familyRelations() as $relation) {
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
     * THE FAMILY'S ONE LINK HELPER — the answer to the plan's "split if the
     * helpers do not unify".
     *
     * They unify, and the reason is structural: this helper does not CHOOSE the
     * leg columns, it APPLIES the ones `MirrorRelationPlan` derived and handed
     * over in `$relation['columns']`. All five join shapes of this family arrive
     * here as a `list<string>`, so the three-column `(account_id, seq,
     * position)` leg set is written by the same code path as the two-column
     * `(account_id, link)` one. There is no per-shape branch to unify, and no
     * opportunity to coerce one shape into another.
     *
     * So `grep -n 'foreign(' ` finds exactly one call in this file, and the leg
     * lists and the on-delete rule live in exactly one place.
     *
     * @param  array{child:string, parent:string, columns:list<string>, reason:string}  $relation
     */
    private static function miscLink(Blueprint $table, array $relation): void
    {
        $table->foreign($relation['columns'], $relation['child'].self::CONSTRAINT_SUFFIX)
            ->references($relation['columns'])
            ->on($relation['parent'])
            ->cascadeOnDelete();
    }

    /**
     * Every relation in this family, DISCOVERED.
     *
     * Two independent filters, neither of them a transcribed list:
     *
     *  - the edges come from `MirrorRelationPlan`, i.e. REFLECTION over the
     *    executed DDL plus the catalog's declared edges — the same derivation the
     *    gate's RED list is built from, so the two cannot disagree;
     *  - membership is "the parent is one of the five roots or a descendant of
     *    one", decided by NAME PREFIX on a table list read off the engine, so a
     *    child created in a different migration file is still found.
     *
     * @return list<array{child:string, parent:string, columns:list<string>, reason:string}>
     */
    private function familyRelations(): array
    {
        $connection = Schema::getConnection();

        if (! Schema::hasTable(self::FAMILY_ROOTS[0])) {
            return [];
        }

        $family = [];
        foreach ($this->tableNames($connection) as $table) {
            foreach (self::FAMILY_ROOTS as $root) {
                if ($table === $root || str_starts_with($table, $root.'_')) {
                    $family[$table] = true;
                }
            }
        }

        $relations = [];
        foreach (MirrorRelationPlan::fromConnection($connection)->singleParent() as $relation) {
            if (! isset($family[$relation['parent']])) {
                continue;
            }

            // The REFUSED list is a FILTER, not an inventory. It is empty for this
            // family today, and kept as a filter anyway because the sibling
            // messages family's list is not: keeping it only in the family that
            // needs one would mean it had never been exercised.
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
     * ("main.PFX_tf_wallpapers") while `hasTable()`/`getForeignKeys()` want the
     * BARE name; both halves of that normalisation are measured, not assumed —
     * `tests/Store/ShippedStoreSchema` documents the same shape.
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
     * already carries the constraint keeps it. This is also what keeps the four
     * `differenceLink` relations idempotent on any engine where that helper's
     * inline declaration DID land.
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
