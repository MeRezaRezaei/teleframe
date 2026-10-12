<?php

declare(strict_types=1);

use Illuminate\Database\Connection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorRelationPlan;

/**
 * Task 4.2, family 4: `tf_saved_star_gifts` and everything descended from it.
 *
 * ## WHAT THIS FAMILY IS
 *
 * A saved-star-gift fact (`StarGift`, the parent `tf_saved_star_gifts`) plus the
 * flat child tables the mirror decomposer emits for its fields — the gift itself,
 * the saved message, the `from_id` pair, the four `can_*_at` dates, the star
 * amounts, the `msg_id` / `saved_id` coordinates, and so on.
 *
 * It USED to also carry one second-level child, `tf_saved_star_gifts_gift_num`,
 * hanging off the gift rather than off the root — the only relation in the family
 * that did. It was never a second-level child: `savedStarGift#41df43fc …
 * gift_num:flags.19?int` is a single int, so the table asserted an object the wire
 * does not nest, and its value is a COLUMN on `tf_saved_star_gifts` as of
 * `2026_10_05_000100_fold_saved_star_gift_num_onto_the_saved_gift_fact.php`. This
 * file needed no code change for that — `familyRelations()` DISCOVERS what exists,
 * so a table that is no longer created is simply no longer found. Only these
 * counts changed.
 *
 * `MirrorRelationPlan::singleParent()` MEASURED 15 single-parent relations in this
 * family on 2026-10-05, by running the anti-rot gate's own derivation and
 * filtering to this family's two parents:
 *
 *     timeout -k 10 900 vendor/bin/phpunit --no-coverage \
 *       --filter test_every_single_parent_relation_has_a_real_constraint \
 *       tests/Schema/Mirror/EveryFkableRelationHasAConstraintTest.php 2>&1 \
 *       | grep -oE "tf_[a-z0-9_]+\([^)]*\) → tf_[a-z0-9_]+" | sort -u \
 *       | grep -E "→ tf_saved_star_gifts(_gift)?$" | wc -l
 *     → 15
 *
 * The measurement was 16 before 2026-10-05 and the grep still carries `(_gift)?`
 * because `tf_saved_star_gifts_gift` — the union flattened as the 1:1 child — is
 * still a member of this family and is still a parent of nothing but the root's
 * own key. ALL FIFTEEN now hang under `tf_saved_star_gifts`; there is no
 * second-level child left, so one root and one prefix still cover every parent and
 * no second hand-written parent list is needed.
 *
 * ## ⭐ THE FAMILY-SPECIFIC HAZARD, AND WHAT WAS ACTUALLY FOUND
 *
 * The plan singles this family out:
 *
 *   verify the `tf_saved_star_gifts_message` and `…_from_id` legs resolve to the
 *   gift's own primary key from the executed schema, not merely a column of the
 *   same name. A same-named column is not the same thing.
 *
 * MEASURED by introspecting the EXECUTED schema (booting the whole dial, then
 * reading `getColumns()` / `getIndexes()`), on sqlite:
 *
 *     tf_saved_star_gifts_message   → account_id, id, text
 *     tf_saved_star_gifts_from_id   → account_id, id, from_id_type, from_id_id
 *     tf_saved_star_gifts primary key → (account_id, id)
 *
 * and independently on real MySQL 8.4.11 via `information_schema`, where the
 * parent's PRIMARY KEY reads `(account_id, id)` with `NON_UNIQUE = 0` and the
 * constraint's `REFERENCED_COLUMN_NAME` reads exactly `account_id, id`:
 *
 *     tf_saved_star_gifts_message   → account_id, id, text
 *     tf_saved_star_gifts_from_id   → account_id, from_id_id, from_id_type, id
 *
 * For BOTH hazard-named children there is **no column of the same name at all**:
 * `…_message` has no `message` column, and the `from_id` value is stored
 * DECOMPOSED as `from_id_type` + `from_id_id`, so there is no `from_id` column
 * either. The only column either table can join its parent on is `id` — the
 * parent's real key leg. So the hazard cannot bite here by accident, and if the
 * derivation HAD produced a same-named leg the `ALTER TABLE` would have named a
 * column that does not exist and failed outright, rather than silently
 * constraining the wrong pair of values.
 *
 * The adjacent shape, which CAN bite and is asserted rather than assumed: two
 * siblings DO carry a same-named column that must NOT be the leg.
 * `tf_saved_star_gifts_msg_id.msg_id` and `tf_saved_star_gifts_saved_id.saved_id`
 * are PAYLOAD values (a message coordinate and a saved-peer id), while both tables
 * join their gift on `id`. A gift constrained on its own `msg_id` would refuse
 * legitimate Telegram data outright, because a gift's `msg_id` is a message
 * coordinate and not a gift id. This file derives the leg from the parent's
 * PRIMARY INDEX rather than by name, which is what makes that outcome
 * unreachable; `tests/Schema/Mirror/TfSavedStarGiftsFamilyForeignKeyTest` proves
 * both halves at runtime and `tests/MySql/TfSavedStarGiftsFamilyFkOnMysqlTest`
 * re-proves it against `information_schema` on the target engine.
 *
 * ## THE JOIN COLUMNS ARE NOT WRITTEN IN THIS FILE — THEY ARE DERIVED
 *
 * Every constraint below goes through ONE helper, `savedStarGiftLink()`, which
 * takes the relation `MirrorRelationPlan` produced. The relation list, the table
 * names and the leg columns are all read off the live schema by reflection;
 * `grep -n 'foreign(' ` below this line finds exactly one call, inside that
 * helper. That is deliberate and it is the same contract
 * `2026_10_04_000800_add_tf_messages_family_foreign_keys.php` and
 * `2026_10_04_000810_add_tf_stars_transactions_family_foreign_keys.php` set: a
 * hand-written leg list is a second copy of the schema, and a second copy drifts.
 * `MirrorRelationPlan` is the same class the anti-rot gate's RED list is built
 * from, so the migration and the gate cannot disagree about which relations exist
 * or what their legs are.
 *
 * `factKey()` in `2026_09_14_200040_create_tf_stars_business_tables.php` is this
 * family's existing column helper — it is what gives every child `account_id` +
 * `id` — but it builds COLUMNS, not links, so it is not the link helper. This file
 * establishes the link helper, exactly as the two sibling families did.
 *
 * ## WHY THE LEGS ARE THE PARENT'S KEY
 *
 * A FOREIGN KEY must reference a UNIQUE index on the parent. Taking the legs from
 * the CHILD's own key instead writes constraints over a column set the parent does
 * not index uniquely, which MySQL 8.4 refuses with error 6125.
 * `MirrorRelationPlan` intersects the parent's primary key with the child's real
 * columns, so the one child in this family whose PK is a strict SUPERSET —
 * `tf_saved_star_gifts_collection_id`, keyed `(account_id, id, position)` — yields
 * `(account_id, id)` and no error 6125. The extra `position` is not lost: it is
 * what makes that relation 1:N, and it stays in the child's own key.
 *
 * MEASURED on MySQL 8.4.11 rather than argued: `information_schema.STATISTICS`
 * reports `NON_UNIQUE = 0` on the PRIMARY index of BOTH parents
 * (`tf_saved_star_gifts` and `tf_saved_star_gifts_gift`), and the real
 * `ALTER TABLE … ADD CONSTRAINT … FOREIGN KEY (account_id,id) REFERENCES
 * tf_saved_star_gifts (account_id,id) ON DELETE CASCADE` applies to both
 * hazard-named children without 6125 and without 3780.
 *
 * ## ON DELETE CASCADE IS DERIVED, NOT CHOSEN PER TABLE
 *
 * One rule for the whole family, in one place, on the same reasoning the two
 * sibling families record: `MirrorFactDecomposer::decomposeChildren()` links a
 * fact's children from the parent row, `MirrorFactWriter::write()` orders the
 * batch parent-first precisely so a child cannot land before its parent, and
 * there is no orphan fact in this write path. A saved-gift child row has no life
 * outside its gift: if the gift row is re-placed under a shorter field vector, its
 * old children must go with it or they are orphaned under a re-placed parent.
 *
 * ## NO RELATION IN THIS FAMILY IS REFUSED — MEASURED, NOT ASSUMED
 *
 * `REFUSED` below is EMPTY, and that is a measurement rather than an omission:
 * all 15 relations carry the parent's own `(account_id, id)` leg set, so none of
 * them needs an exception. The constant is kept, and the filter that reads it is
 * kept, because the sibling `tf_messages` family DOES carry a refusal
 * (`tf_messages_reply_markup_rows_buttons`, whose parent-key intersection picks
 * the button's index WITHIN its row instead of the row it hangs under). A filter
 * that is only ever added for the family that needs one is a filter that has
 * never been exercised. The gate asserts the constant is an array of
 * `child => reason`, asserts its keys are a subset of the discovered family
 * relations, and asserts the constrained count is `discovered − refused` — so a
 * refusal added without its reason, or aimed at a table outside the family, fails
 * there.
 *
 * ## NO PRIMARY KEY IS WIDENED OR NARROWED TO MAKE A CONSTRAINT FIT
 *
 * Every leg here is the parent's EXISTING `(account_id, id)` primary key as the
 * executed schema reports it, and every child already carries both columns.
 * Nothing in this file changes a key, and this file is not the place to fix a
 * signedness divergence if one existed — see the MySQL lane note below. Had a
 * relation needed a key change, it would have been left out and the mechanical
 * reason recorded in `REFUSED`: widening a parent while its children stay
 * unwidened produces children whose `id` resolves to no parent, which is a NEW
 * inconsistency and strictly worse than the state it replaced.
 *
 * ## ADDITIVE, AND NO SHIPPED MIGRATION IS EDITED
 *
 * `git cat-file -e v1.3.0:src/Laravel/Migrations/2026_09_14_200040_create_tf_stars_business_tables.php`
 * succeeds (also true at v1.3.1 and v1.3.2), so this family's create migration is
 * in a shipped tag and is therefore NOT editable in place. An in-place edit would
 * leave every install that already ran it unconstrained forever, because that
 * migration is long since recorded as applied. The other files that touch these
 * tables — `2026_10_04_000400_add_star_gift_host_and_availability_columns.php`,
 * `2026_10_04_000600_retype_saved_star_gift_key_to_bigint.php` and
 * `2026_10_04_000700_add_tenant_foreign_keys_to_mirror_tables.php` — are committed
 * on `main` but in no tag at all; none of them is edited here either. The add
 * happens HERE, in a new file — the same convention as `2026_10_04_000100`,
 * `…_000700`, `…_000800` and `…_000810`.
 *
 * ## WHY sqlite IS NOT THE GATE
 *
 * sqlite cannot raise 1452 (orphan refused), 3780 (FK column signedness), 6125
 * (parent key not unique), 1059 (constraint name over 64 characters) or 1170
 * (TEXT in a PRIMARY KEY) at all, and four of those five would have shipped green
 * in a sqlite-only suite. That is not hypothetical for THIS family:
 * `2026_10_04_000600_retype_saved_star_gift_key_to_bigint.php` deliberately
 * retyped the sixteen children's `id` to BIGINT (that list still names
 * `tf_saved_star_gifts_gift_num`, which was live when 000600 ran and is dropped by
 * 2026_10_05_000100 — the entry is harmless, because 000600 skips a table that is
 * not there) and deliberately left the PARENT
 * `tf_saved_star_gifts` out of that list, so parent and child leg types are only
 * comparable on a real engine. MEASURED there, because it was the open risk:
 * parent `account_id` is `bigint unsigned` and `id` is `bigint`, and every one of
 * the sixteen children that existed then read identically — 0 of 16 at risk of
 * 3780. Fifteen of those sixteen tables still exist; the measurement stands for
 * the fifteen. The gate that proves this
 * migration is ACCEPTED by the target engine is the MySQL lane
 * (`tests/MySql/TfSavedStarGiftsFamilyFkOnMysqlTest.php`), not this file's own
 * sqlite exercise. The sqlite gate proves the constraints BITE — declared,
 * enforced, over the parent's real key, and gone when the file is.
 *
 * ## THE COST, STATED RATHER THAN DISCOVERED
 *
 * Each `ALTER TABLE … ADD CONSTRAINT` is a separate, non-atomic rebuild on InnoDB.
 * `2026_10_04_000700` measured 265 of them at a 23.35 s floor on empty tables;
 * these 15 are the same shape.
 */
return new class extends Migration
{
    /** The family ROOT. Descendants are discovered, never listed. */
    private const FAMILY_ROOT = 'tf_saved_star_gifts';

    /**
     * The family PREFIX — a descendant of the root keeps its name.
     *
     * This is also what pulls `tf_saved_star_gifts_gift` INTO the family, which is
     * how one root covers both parents: the gift is a descendant of the root by
     * name. MEASURED on the built schema:
     * `str_starts_with('tf_saved_star_gifts_gift', 'tf_saved_star_gifts_')` is true.
     *
     * The prefix's ORIGINAL reason — that the gift's own child
     * `tf_saved_star_gifts_gift_num` then had its parent inside the family too —
     * is gone, because that child is gone. The prefix is kept because it is how the
     * gift gets into the family at all, and because it is what makes a table created
     * by some future migration in this family be found without a second list. It is
     * a discovery mechanism, not an inventory, so it needs no reason beyond that.
     */
    private const FAMILY_PREFIX = 'tf_saved_star_gifts_';

    /**
     * The suffix every constraint this file creates carries.
     *
     * Length, MEASURED not chosen. MySQL caps an identifier at exactly 64
     * characters (probed one character at a time on 8.4.11; 65 is refused with
     * `ERROR 1059 … Identifier name is too long`) and sqlite caps it at none.
     * The longest table in THIS family is
     * `tf_saved_star_gifts_drop_original_details_stars`, MEASURED at 47
     * characters:
     *
     *     php -r 'echo strlen("tf_saved_star_gifts_drop_original_details_stars");'
     *     → 47
     *
     * so the budget for a suffix is 17. `_ssg_parent_fk` is 14, so the longest
     * name this file can produce is 61. A name that is too long passes every
     * sqlite test and fails on the first real ALTER — which is exactly how the
     * tenant migration's first spelling, `_account_tenant_fk` (18), shipped green
     * in the suite and was refused the first real migration.
     *
     * Uniqueness: it must NOT collide with `_msg_parent_fk`
     * (`2026_10_04_000800`), `_st_parent_fk` (`2026_10_04_000810`) or
     * `_acc_tenant_fk` (`2026_10_04_000700`) — a shared suffix makes `down()`
     * unable to tell whose constraints it holds, which is what made the tenant
     * migration's `down()` remove 271 instead of 265.
     */
    private const CONSTRAINT_SUFFIX = '_ssg_parent_fk';

    /**
     * Relations in this family that are REFUSED, with the reason. EMPTY here,
     * deliberately — see the docblock's "NO RELATION IN THIS FAMILY IS REFUSED".
     *
     * A refusal is a NAMED exception, never a silent skip: an unnamed skip is
     * indistinguishable from a bug in the discovery.
     * `tests/Schema/Mirror/TfSavedStarGiftsFamilyForeignKeyTest` asserts every key
     * here is a real discovered family relation carrying a non-empty reason, so a
     * refusal added by accident — or aimed at a table outside this family — fails
     * there.
     *
     * @var array<string, string>
     */
    private const REFUSED = [];

    /**
     * How many constraints this run added.
     *
     * Recorded, not returned, so a reader can see the loop skipped none. The gate
     * is what proves it — it asserts the declaration on every discovered family
     * relation from the executed schema rather than trusting this.
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
                self::savedStarGiftLink($blueprint, $relation);
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
     * Every constraint this file creates goes through here, so the on-delete rule
     * and the leg list are derived from a single place rather than transcribed into
     * N call sites. Mirrors `linkChild()` / `participantLink()` /
     * `channelUpdateLink()` / `differenceLink()` / `tenantLink()` /
     * `messageLink()` / `transactionLink()` in the shipped migrations, which is
     * why those shapes exist at all.
     *
     * The leg list is `$relation['columns']`, which `MirrorRelationPlan` derived
     * from the PARENT'S PRIMARY KEY intersected with the child's real columns — it
     * is never written here, and it is never derived from a column NAME.
     *
     * @param  array{child:string, parent:string, columns:list<string>, reason:string}  $relation
     */
    private static function savedStarGiftLink(Blueprint $table, array $relation): void
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
     *  - membership is "the parent is the family root or one of its descendants",
     *    decided by NAME PREFIX on a table list read off the engine, so a child
     *    created in a different migration file is still found.
     *
     * @return list<array{child:string, parent:string, columns:list<string>, reason:string}>
     */
    private function familyRelations(): array
    {
        $connection = Schema::getConnection();
        $tables = $this->tableNames($connection);

        // A family member is a table whose name extends the root's — the same
        // longest-prefix ancestry rule `bin/generate-relation-graph.php` and
        // `MirrorRelationPlan` document, applied one level down: the parent of a
        // family relation is inside the family, so the parent set is the family
        // ROOT plus every descendant discovered above it.
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

            // The REFUSED list is a FILTER, not an inventory. It is empty for this
            // family today, and it is kept as a filter anyway because the sibling
            // `tf_messages` family's list is not: keeping the filter only in the
            // family that needs it would mean the filter has never been exercised.
            // `tests/Schema/Mirror/TfSavedStarGiftsFamilyForeignKeyTest` asserts
            // its shape, its membership and the count arithmetic, so it cannot rot
            // into dead code silently.
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
     * ("main.PFX_tf_saved_star_gifts") while `hasTable()`/`getForeignKeys()` want
     * the BARE name; both halves of that normalisation are measured, not assumed —
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
