<?php

declare(strict_types=1);

use Illuminate\Database\Connection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorRelationPlan;

/**
 * Task 4.2, first family: `tf_messages` and everything descended from it.
 *
 * ## WHY THIS FAMILY IS FIRST
 *
 * `MirrorRelationPlan` classifies every declared intra-mirror edge by REFLECTION
 * over the executed DDL, and MEASURED on 2026-10-04 it found 207 single-parent
 * relations of which 165 carried no constraint at all
 * (`tests/Schema/Mirror/EveryFkableRelationHasAConstraintTest.php`, whose RED
 * list IS the work list — read it from there, never from a list written here).
 *
 * The messages family is the largest single block of that list and the one
 * whose parent key is already exactly the child's own key columns, so it is the
 * cheapest honest first slice.
 *
 * ## THE JOIN COLUMNS ARE NOT WRITTEN IN THIS FILE — THEY ARE DERIVED
 *
 * Every other place in this dial that wires a child to a parent routes it
 * through a shared Blueprint helper (`linkChild()`, `participantLink()`,
 * `channelUpdateLink()`, `differenceLink()` in the create migrations;
 * `tenantLink()` in `2026_10_04_000700`) precisely so the columns and the
 * on-delete rule live in ONE place and 265 call sites cannot drift apart.
 *
 * The messages family has no such helper — its four existing constraints are
 * hand-written column lists inside `2026_09_14_299999_create_tf_foreign_keys.php`
 * — so this file establishes one (`messageLink()`) and derives every leg list
 * through it from `MirrorRelationPlan`, the same class the gate derives its
 * work list from. The migration and the gate therefore cannot disagree about
 * which relations exist or what their legs are: a disagreement is impossible
 * by construction, not by review.
 *
 * Consequence, stated because it is the point: there is NO hand-written join
 * column list, no hand-written table list, and no hand-written constraint count
 * used to decide WHAT gets constrained. `grep -n 'foreign(' ` below this line
 * finds one call, inside the helper.
 *
 * ## WHY THE LEGS ARE THE PARENT'S KEY
 *
 * A FOREIGN KEY must reference a UNIQUE index on the parent. Taking the legs
 * from the CHILD's primary key instead — which the Task 4.1 RED step did, and
 * measured 175 relations — writes ten constraints referencing a non-unique
 * column set, refused by MySQL 8.4 with error 6125. `MirrorRelationPlan`
 * intersects the parent's primary key with the child's real columns, so a
 * child whose key is a strict SUPERSET of its parent's (1:N positioned vectors)
 * yields the parent's key and no error 6125.
 *
 * ## ON DELETE CASCADE IS DERIVED, NOT CHOSEN PER TABLE
 *
 * One rule for the whole family, in one place. A message fact's children have
 * no life outside it: `MirrorFactDecomposer::decomposeChildren()` links them
 * from the parent row and there is no orphan fact in the write path, and
 * `SafeDelete` prunes a message by its own coordinates — so a re-delivered
 * message with a shorter field vector must take its old children with it, or
 * the dropped ones are orphaned under a re-placed parent. That is exactly the
 * `2026_09_14_299999` reasoning for the three message constraints already in
 * the dial, and this file applies it to the rest of the family by derivation
 * rather than repeating it 37 times.
 *
 * MEASURED on MySQL 8.4.11 (`TELEFRAME_MYSQL=1 vendor/bin/phpunit
 * tests/MySql`): a valid child row lands, an orphan is refused `ERROR 1452`,
 * and deleting the parent takes the child with it.
 *
 * ## ONE RELATION IN THIS FAMILY IS REFUSED, AND IT IS NAMED
 *
 * `tf_messages_reply_markup_rows_buttons → tf_messages_reply_markup_rows`.
 *
 * The parent row's key is `(account_id, peer_type, peer_id, id, position)`; the
 * button's own key is that PLUS `(position, parent_position)`. Intersecting the
 * parent's key with the button's columns picks the button's `position` — its
 * index WITHIN the row — which is NOT the row's position. `parent_position` is.
 *
 * `2026_09_14_299999_create_tf_foreign_keys.php` says so in its own comment
 * ("the button's own `position` is only its index WITHIN that row and must
 * never be joined here") and already ships the correct constraint,
 * `tf_reply_markup_buttons_row_fk`, over
 * `(account_id,peer_type,peer_id,id,parent_position) → (…,position)`.
 *
 * So adding the plan-derived leg set here would be a SECOND constraint from the
 * same child to the same parent, on the wrong column, and it would refuse
 * legitimate Telegram data: an ordinary one-button-per-row keyboard puts every
 * button at `position = 0` while its rows sit at `position = 0,1,2,…`, so rows
 * 1 and 2 would be unrepresentable.
 *
 * This is therefore REFUSED rather than written, listed in `REFUSED` below with
 * its reason, and it is a DEFECT IN THE DERIVATION — `MirrorRelationPlan` picks
 * a positioned child's OWN `position` over its `parent_position` — not a
 * keying bug in the schema and not a missing constraint. It gets its own task
 * with its own RED, in `MirrorRelationPlan`, which this task does not edit.
 * Until then the gate reports this one relation as missing, which is the honest
 * reading: nothing enforces the derived leg set, because the derived leg set is
 * wrong.
 *
 * ## ADDITIVE, AND NO SHIPPED MIGRATION IS EDITED
 *
 * `git cat-file -e <tag>:<path>` — the four create migrations this family lives
 * in (`2026_09_14_200010`, `2026_09_14_200012`, `2026_09_29_000200`, and
 * `2026_09_14_299999`) are all in tag v1.3.x and are therefore NOT editable in
 * place. An in-place edit would leave every install that already ran them
 * unconstrained forever, because those migrations are long since recorded as
 * applied. The add happens HERE, in a new file — the same convention as
 * `2026_10_04_000100`, `…_000200` and `…_000700`.
 *
 * ## WHY sqlite IS NOT THE GATE
 *
 * sqlite has no `ALTER TABLE … ADD CONSTRAINT`: Laravel's sqlite grammar
 * achieves the add by rebuilding the table, and the rebuild PRESERVES a
 * composite FK the table already had (MEASURED — `tf_messages_entities` keeps
 * its four-column link to `tf_messages` and gains the tenant leg). What sqlite
 * cannot do is `dropForeign()` BY NAME ("This database driver does not support
 * dropping foreign keys by name"), and it records neither constraint names nor
 * column signedness — so `down()` REFUSES on a driver that cannot express
 * ownership, exactly as `2026_10_04_000700` does, rather than either dropping
 * another migration's constraints or reporting a rollback that changed nothing.
 *
 * The gate that proves this migration works on the TARGET engine is the MySQL
 * lane (`tests/MySql/`), not this file's own sqlite exercise. sqlite cannot
 * raise 3780 (signedness), 6125 (non-unique parent key), 1059 (constraint name
 * over 64 characters) or 1170 (TEXT in a PRIMARY KEY) at all, and three of
 * those four would have shipped green here.
 *
 * ## THE COST, STATED RATHER THAN DISCOVERED
 *
 * Each `ALTER TABLE … ADD CONSTRAINT` is a separate, non-atomic rebuild on
 * InnoDB. `2026_10_04_000700` measured 265 of them at a 23.35 s floor on empty
 * tables; this file's 37 are the same shape. The mirror is nearly empty right
 * now, which is the cheapest window that will ever exist; after the history fill
 * this becomes an online-DDL problem.
 */
return new class extends Migration
{
    /** The family ROOT. Descendants are discovered, never listed. */
    private const FAMILY_ROOT = 'tf_messages';

    /** The family PREFIX — a descendant of the root keeps its name. */
    private const FAMILY_PREFIX = 'tf_messages_';

    /**
     * The suffix every constraint this file creates carries.
     *
     * Length, MEASURED not chosen: MySQL caps an identifier at exactly 64
     * characters (probed one character at a time on 8.4.11; 65 is refused with
     * `ERROR 1059 … Identifier name is too long`) and sqlite caps it at none.
     * The longest table name in THIS family is
     * `tf_messages_reply_markup_rows_buttons` at 35 characters, so the budget
     * for a suffix is 29. `_msg_parent_fk` is 14, and the longest name this
     * file can produce is 49.
     *
     * A name that is too long passes every sqlite test and fails on the first
     * real ALTER — which is exactly how the first spelling of the tenant
     * suffix, `_account_tenant_fk` (18) against a 49-character table, shipped
     * green in the suite and refused the first real migration.
     *
     * Uniqueness: it must NOT collide with `_acc_tenant_fk`
     * (`2026_10_04_000700`), with `tf_messages_media_message_fk` /
     * `tf_messages_entities_message_fk` (`2026_09_14_299999`), or with
     * `tf_reply_markup_buttons_row_fk` — MEASURED on the tenant migration: a
     * shared suffix made its `down()` unable to tell whose constraints it held
     * and it removed 271 instead of 265.
     */
    private const CONSTRAINT_SUFFIX = '_msg_parent_fk';

    /**
     * Relations in this family that are REFUSED, with the reason.
     *
     * A refusal is a NAMED exception, never a silent skip: an unnamed skip is
     * indistinguishable from a bug in the discovery, and this file's own gate
     * (`tests/Schema/Mirror/TfMessagesFamilyForeignKeyTest`) asserts this list
     * is exactly the set of family relations it declined to constrain, so a
     * refusal added by accident fails there.
     *
     * @var array<string, string>
     */
    private const REFUSED = [
        'tf_messages_reply_markup_rows_buttons' => 'the parent row\'s key ends in `position` and the button\'s '
            .'own key carries `position` plus `parent_position`, so the parent-key intersection picks the '
            .'button\'s index WITHIN the row rather than the row it hangs under. '
            .'2026_09_14_299999 already ships the correct constraint, '
            .'`tf_reply_markup_buttons_row_fk`, over '
            .'`(account_id,peer_type,peer_id,id,parent_position) → (…,position)`, and says in its own '
            .'comment that the button\'s `position` "must never be joined here". The derived leg set would '
            .'refuse an ordinary one-button-per-row keyboard, whose buttons all sit at position 0 under rows '
            .'at positions 0,1,2,… This is a DEFECT IN MirrorRelationPlan\'s leg derivation (it does not '
            .'prefer a `parent_position` sibling), not a keying bug and not a missing constraint; it gets '
            .'its own task with its own RED in MirrorRelationPlan, which this task does not edit.',
    ];

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
        if (! Schema::hasTable(self::FAMILY_ROOT)) {
            return;
        }

        $added = 0;
        foreach ($this->familyRelations() as $relation) {
            if ($this->existingConstraint($relation) !== null) {
                continue;
            }

            Schema::table($relation['child'], function (Blueprint $blueprint) use ($relation): void {
                self::messageLink($blueprint, $relation);
            });
            $added++;
        }

        $this->added = $added;
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::FAMILY_ROOT)) {
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
                    .'2026_09_14_299999_create_tf_foreign_keys.php (tf_messages_media_message_fk, '
                    .'tf_messages_entities_message_fk, tf_reply_markup_buttons_row_fk) or to '
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
     * `tenantLink()` in the shipped create migrations, which is why those five
     * shapes exist at all.
     *
     * @param  array{child:string, parent:string, columns:list<string>, reason:string}  $relation
     */
    private static function messageLink(Blueprint $table, array $relation): void
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
     *  - the edges come from `MirrorRelationPlan`, i.e. from REFLECTION over
     *    the executed DDL plus the catalog's declared edges — the same
     *    derivation the gate's RED list is built from, so the two cannot
     *    disagree;
     *  - membership is "the parent is the family root or one of its
     *    descendants", decided by NAME PREFIX on a table list read off the
     *    engine. `tf_messages_rich_message` and `tf_messages_service_action`
     *    live in DIFFERENT migration files from the rest of the family
     *    (`2026_09_29_000200`, `2026_09_14_200012`) and are still found,
     *    because nothing here knows which file created what.
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

            // The REFUSED list is a FILTER, not an inventory. Without this line
            // the constant is dead code while the file docblock claims the wrong
            // leg set is declined, and the refusal holds only by ACCIDENT:
            // `existingConstraint()` happens to short-circuit the refused
            // relation because `2026_09_14_299999`'s correct constraint
            // references the parent's own key, which is exactly what the faulty
            // derivation produces. Drop that shipped constraint and the faulty
            // one is created on the next migrate — refusing it here is what
            // makes the claim true rather than coincidental.
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
     * ("main.PFX_tf_messages") while `hasTable()`/`getForeignKeys()` want the
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
