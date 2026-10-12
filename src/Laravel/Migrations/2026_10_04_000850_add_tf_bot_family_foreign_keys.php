<?php

declare(strict_types=1);

use Illuminate\Database\Connection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorRelationPlan;

/**
 * Task 4.2, family 5: the `tf_bot_*` block and the `tf_attach_menu_bots*` block.
 *
 * ## WHAT THIS FAMILY IS — MEASURED, NOT SIZED FROM THE PLAN
 *
 * Nineteen single-parent relations across FIVE parents: `tf_bot_infos`,
 * `tf_bot_infos_description`, `tf_bot_inline_results`, `tf_bot_apps` and
 * `tf_attach_menu_bots`. MEASURED on 2026-10-05 by re-running the anti-rot gate
 * and reading its own RED list — never by copying a figure out of a table:
 *
 *     timeout -k 10 900 vendor/bin/phpunit --no-coverage \
 *       --filter test_every_single_parent_relation_has_a_real_constraint \
 *       tests/Schema/Mirror/EveryFkableRelationHasAConstraintTest.php 2>&1 \
 *       | grep -oE "tf_[a-z0-9_]+\([^)]*\) → tf_[a-z0-9_]+" | sort -u \
 *       | grep -E "→ tf_bot_infos(_description)?$|→ tf_bot_inline_results$|→ tf_bot_apps$|→ tf_attach_menu_bots$"
 *
 * and independently, from the executed dial rather than from the gate's list —
 * 23 tables live in the two name-spaces below, of which 5 are parents, so
 * 23 − 5 + 1 = 19 children, and `MirrorRelationPlan::singleParent()` MEASURED
 * `singleParent => 207, polymorphic => 8, unconstrained => 0` tree-wide with
 * exactly 19 of those 207 inside this family.
 *
 * ## THIS FAMILY SPANS TWO NAME-SPACES, AND A ONE-ROOT RULE CANNOT REACH BOTH
 *
 * The `tf_messages` and `tf_stars_transactions` families are each a single root
 * plus its descendants, so membership is `str_starts_with($table, $root.'_')` and
 * one constant does the whole job. This family does not have that shape:
 * `tf_attach_menu_bots` extends NO `tf_bot_*` table — it is a separate
 * single-ctor fact whose natural key is the bot's `bot_id`, not an `id` — so a
 * one-root rule would miss the entire `tf_attach_menu_bots` half and still
 * report a plausible count.
 *
 * `FAMILY_NAMESPACES` below is therefore a LIST of namespace strings, and it is
 * the only hand-named value involved in deciding what gets constrained. It is
 * kept honest by a gate assertion rather than by review:
 * `tests/Schema/Mirror/TfBotFamilyForeignKeyTest::test_every_family_namespace_table_that_has_a_family_parent_is_constrained`
 * re-derives, by its own ancestry code, every table in these namespaces that
 * descends from inside the family, and requires each to carry a constraint. A
 * namespace added by mistake, or a member silently skipped, fails there.
 *
 * Everything BELOW the namespaces — the relation list, the table names, the leg
 * columns, the constraint count — is derived by reflection over the executed DDL.
 *
 * ## THE JOIN COLUMNS ARE NOT WRITTEN IN THIS FILE — THEY ARE DERIVED
 *
 * Every constraint below goes through ONE helper, `botFactLink()`, which takes
 * the relation `MirrorRelationPlan` produced. `grep -n 'foreign(' ` below this
 * line finds exactly one call, inside that helper. A hand-written leg list is a
 * second copy of the schema, and a second copy drifts.
 *
 * ## THE FAMILY HOLDS TWO JOIN SHAPES, AND THE HELPER HANDLES BOTH BY DERIVATION
 *
 * This is the hazard the plan names for family 5, stated here as what it
 * actually is rather than as a worry:
 *
 *   tf_attach_menu_bots_icons(account_id,bot_id)      → tf_attach_menu_bots
 *   tf_attach_menu_bots_peer_types(account_id,bot_id) → tf_attach_menu_bots
 *   the other 17                                       (account_id,id)
 *
 * Two helpers that each hard-code a shape would be the transcribed answer. One
 * helper that reads `$relation['columns']` — which is whatever the plan derived
 * for THIS relation — is the derived answer, and it is why the same call site
 * can carry `(account_id,bot_id)` and `(account_id,id)` without either appearing
 * in this file. The gate asserts both shapes are present AND both are
 * constrained, so "I handled both" is a measurement here rather than a claim.
 *
 * ## WHY THE TWO `bot_id` RELATIONS ARE CONSTRAINED AND NOT REFUSED — THE EVIDENCE
 *
 * The prescribed remedy for a leg set whose parent lacks a UNIQUE index is to
 * leave the relation out and record the mechanical reason, because MySQL 8.4
 * refuses such a constraint with error 6125 and the other option — adding the
 * missing index — is a key change, which rule 2 forbids. So the question had to
 * be MEASURED rather than assumed, and it was:
 *
 *     php -r '<boot ShippedStoreSchema over UpdateIngestor::migrationPaths(), then
 *              getSchemaBuilder()->getIndexes() on the family tables>'
 *
 * on the executed dial, SQLite, 2026-10-05:
 *
 *   | table                        | indexes                                        |
 *   |------------------------------|------------------------------------------------|
 *   | `tf_attach_menu_bots`        | **PK(`account_id`,`bot_id`) unique=true**       |
 *   | `tf_attach_menu_bots_icons`  | PK(`account_id`,`bot_id`,`position`), idx(`account_id`,`bot_id`) |
 *   | `tf_attach_menu_bots_peer_types` | PK(`account_id`,`bot_id`,`position`), idx(`account_id`,`bot_id`) |
 *
 * `bot_id` is a column of the parent's PRIMARY KEY, not merely a column that
 * happens to share the name — the distinction family 4's hazard explicitly
 * warns about, and the difference between a constraint MySQL accepts and one it
 * refuses with 6125. Both children's own keys are a strict SUPERSET of the
 * parent's (`+ position`), which is why the legs are the parent's key and not
 * the child's: a FOREIGN KEY must reference a UNIQUE index.
 *
 * So `REFUSED` below is EMPTY, and that is a measurement rather than an
 * omission. It is kept, and its filter is kept, because the sibling `tf_messages`
 * family's list is NOT empty
 * (`tf_messages_reply_markup_rows_buttons`, whose parent-key intersection picks
 * the button's index WITHIN its row instead of the row it hangs under). A
 * filter that only ever exists for the families that need one is a filter that
 * has never been exercised, and the gate asserts its shape, its membership and
 * the `discovered − refused` arithmetic so it cannot rot into dead code.
 *
 * The verdict is not left to this paragraph: the gate re-measures it at runtime
 * from the engine's own index listing and fails if the parent's UNIQUE index
 * disappears, naming the actual index list in the failure message.
 *
 * ## WHY THE LEGS ARE THE PARENT'S KEY
 *
 * `MirrorRelationPlan` intersects the parent's primary key with the child's real
 * columns, so a child whose key is a strict SUPERSET of its parent's yields the
 * parent's key and no error 6125. Three children in this family are such a
 * superset — `tf_attach_menu_bots_icons`, `tf_attach_menu_bots_peer_types`
 * (both `+ position`) and `tf_bot_infos_commands` (`+ position`). The extra
 * `position` is not lost: it is what makes the relation 1:N, and it stays in the
 * child's key.
 *
 * ## ON DELETE CASCADE IS DERIVED, NOT CHOSEN PER TABLE
 *
 * One rule for the whole family, in one place, on the reasoning the `tf_messages`
 * and `tf_stars_transactions` families record: `MirrorFactDecomposer` links a
 * fact's children from the parent row, `MirrorFactWriter::write()` orders the
 * batch parent-first precisely so a child cannot land before its parent, and
 * there is no orphan fact in this write path. A bot fact's decomposed children
 * have no life outside it, so a re-delivered `botInfo` with a shorter field
 * vector must take its old children with it.
 *
 * ## NO PRIMARY KEY IS WIDENED OR NARROWED TO MAKE A CONSTRAINT FIT
 *
 * Nothing in this file changes a key. Had a relation needed one, it would have
 * been left out and the mechanical reason recorded in `REFUSED` — widening a
 * parent while its children stay unwidened produces children whose key resolves
 * to no parent, which is a NEW inconsistency and strictly worse than the state it
 * replaced.
 *
 * ## ADDITIVE, AND NO SHIPPED MIGRATION IS EDITED
 *
 * `git cat-file -e <tag>:<path>` — this family's create migration
 * (`2026_09_14_200041_create_tf_bots_tables.php`) and the tenant migration
 * (`2026_10_04_000700`) are in tag v1.3.x and are therefore NOT editable in
 * place. An in-place edit would leave every install that already ran them
 * unconstrained forever, because those migrations are long since recorded as
 * applied. The add happens HERE, in a new file — the same convention as
 * `2026_10_04_000100`, `…_000200`, `…_000700`, `…_000800` and `…_000810`.
 *
 * ## WHY sqlite IS NOT THE GATE
 *
 * sqlite cannot raise 3780 (FK column signedness), 6125 (parent key not unique),
 * 1059 (name over 64 characters) or 1170 (TEXT in a PRIMARY KEY) at all, and
 * three of those four would have shipped green here. The gate that proves this
 * migration works on the TARGET engine is the MySQL lane
 * (`tests/MySql/TfBotFamilyFkOnMysqlTest.php`), not this file's own sqlite
 * exercise. The sqlite gate here proves the constraints BITE — declared,
 * enforced, and gone when the file is.
 *
 * ## THE COST, STATED RATHER THAN DISCOVERED
 *
 * Each `ALTER TABLE … ADD CONSTRAINT` is a separate, non-atomic rebuild on
 * InnoDB. `2026_10_04_000700` measured 265 of them at a 23.35 s floor on empty
 * tables; these 19 are the same shape.
 */
return new class extends Migration
{
    /**
     * The family's NAME-SPACES, in one place because a single root cannot
     * express this family.
     *
     * See the docblock's "THIS FAMILY SPANS TWO NAME-SPACES". These are
     * namespace strings and nothing else is hand-named; every table below them
     * is discovered.
     *
     * @var list<string>
     */
    private const FAMILY_NAMESPACES = ['tf_bot_', 'tf_attach_menu_bots'];

    /**
     * The suffix every constraint this file creates carries.
     *
     * Length, MEASURED not chosen. MySQL caps an identifier at exactly 64
     * characters (probed one character at a time on 8.4.11; 65 is refused with
     * `ERROR 1059 … Identifier name is too long`) and sqlite caps it at none.
     * The longest table name in THIS family is `tf_bot_inline_results_send_message`
     * at 34 characters, MEASURED on the executed dial, so the budget for a
     * suffix is 30. `_bot_parent_fk` is 14, so the longest name this file can
     * produce is 48.
     *
     * A name that is too long passes every sqlite test and fails on the first
     * real ALTER — which is exactly how the tenant migration's first spelling,
     * `_account_tenant_fk` (18) against a 49-character table, shipped green in
     * the suite and was refused the first real migration.
     *
     * Uniqueness: it must NOT collide with `_acc_tenant_fk`
     * (`2026_10_04_000700`), `_msg_parent_fk` (`2026_10_04_000800`) or
     * `_st_parent_fk` (`2026_10_04_000810`) — MEASURED on the tenant migration:
     * a shared suffix made its `down()` unable to tell whose constraints it held
     * and it removed 271 instead of 265.
     */
    private const CONSTRAINT_SUFFIX = '_bot_parent_fk';

    /**
     * Relations in this family that are REFUSED, with the reason.
     *
     * EMPTY, and that is a measurement rather than an omission — see the
     * docblock's "WHY THE TWO `bot_id` RELATIONS ARE CONSTRAINED AND NOT
     * REFUSED". The filter below reads it anyway, because the sibling
     * `tf_messages` family's list is not empty.
     *
     * @var array<string, string>
     */
    private const REFUSED = [];

    /**
     * How many constraints this run added.
     *
     * Recorded, not returned, so a reader can see the loop skipped none. The
     * gate is what proves it — it asserts the declaration on every discovered
     * family relation from the executed schema rather than trusting this.
     */
    private int $added = 0;

    public function up(): void
    {
        // Nothing in this family exists, so there is nothing to constrain. The
        // guard is on the family tables rather than on one root, because this
        // family has no single root to test.
        if ($this->familyTables() === []) {
            return;
        }

        $added = 0;
        foreach ($this->familyRelations() as $relation) {
            // Idempotent: an install that already carries the constraint — or a
            // re-run against a partially-applied dial — must not raise a
            // duplicate-constraint error. Read from the executed schema, not from
            // a list, so "already declared" is a fact and not a guess.
            if ($this->existingConstraint($relation) !== null) {
                continue;
            }

            Schema::table($relation['child'], function (Blueprint $blueprint) use ($relation): void {
                self::botFactLink($blueprint, $relation);
            });
            $added++;
        }

        $this->added = $added;
    }

    public function down(): void
    {
        if ($this->familyTables() === []) {
            return;
        }

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
     * THE FAMILY'S ONE LINK HELPER — and the reason both join shapes need no
     * second one.
     *
     * Every constraint this file creates goes through here, so the on-delete
     * rule and the leg list are derived from a single place rather than
     * transcribed into N call sites. Mirrors `linkChild()` /
     * `participantLink()` / `channelUpdateLink()` / `differenceLink()` /
     * `tenantLink()` / `messageLink()` / `transactionLink()` in the shipped
     * migrations, which is why those shapes exist at all.
     *
     * It reads `$relation['columns']` — whatever the plan derived for THIS
     * relation — so `(account_id,bot_id)` and `(account_id,id)` pass through the
     * SAME call site without either leg list appearing in this file. A helper
     * per shape would have been the transcribed answer.
     *
     * @param  array{child:string, parent:string, columns:list<string>, reason:string}  $relation
     */
    private static function botFactLink(Blueprint $table, array $relation): void
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
     *  - the edges come from `MirrorRelationPlan`, i.e. from REFLECTION over the
     *    executed DDL plus the catalog's declared edges — the same derivation the
     *    gate's RED list is built from, so the two cannot disagree;
     *  - membership is the family NAME-SPACES above, matched against a table list
     *    read off the engine, so a child created in a different migration file is
     *    still found.
     *
     * @return list<array{child:string, parent:string, columns:list<string>, reason:string}>
     */
    private function familyRelations(): array
    {
        $connection = Schema::getConnection();

        $family = array_flip($this->familyTables());

        $relations = [];
        foreach (MirrorRelationPlan::fromConnection($connection)->singleParent() as $relation) {
            if (! isset($family[$relation['parent']])) {
                continue;
            }

            // The REFUSED list is a FILTER, not an inventory. It is empty for
            // this family today, and it is kept as a filter anyway because the
            // sibling messages family's list is not: keeping the filter only in
            // the family that needs it would mean the filter has never been
            // exercised. The gate asserts its shape, its membership and the
            // `discovered − refused` arithmetic, so it cannot rot silently.
            if (isset(self::REFUSED[$relation['child']])) {
                continue;
            }

            $relations[] = $relation;
        }

        return $relations;
    }

    /**
     * Every table inside the family's name-spaces, read off the engine.
     *
     * @return list<string>
     */
    private function familyTables(): array
    {
        $members = [];

        foreach ($this->tableNames(Schema::getConnection()) as $table) {
            foreach (self::FAMILY_NAMESPACES as $namespace) {
                if (str_starts_with($table, $namespace)) {
                    $members[] = $table;

                    break;
                }
            }
        }

        return $members;
    }

    /**
     * The bare table names on this connection, prefix- and schema-stripped.
     *
     * `getTableListing()` returns SCHEMA-QUALIFIED, PREFIXED names
     * ("main.PFX_tf_bot_infos") while `hasTable()`/`getForeignKeys()` want the
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
