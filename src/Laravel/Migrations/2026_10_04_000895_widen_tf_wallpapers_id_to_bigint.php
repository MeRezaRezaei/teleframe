<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorRelationPlan;

/**
 * Widen `tf_wallpapers.id` from `int` to `bigint` — the leg that made the WHOLE
 * shipped dial unmigratable on MySQL.
 *
 * ## THE DEFECT, MEASURED ON REAL MySQL 8.4.11, NOT ON A DOCBLOCK
 *
 * The first run of `tests/MySql/**` that ever reached a migrated schema
 * produced, for every test:
 *
 *     Illuminate\Database\QueryException: SQLSTATE[HY000]: General error: 3780
 *     Referencing column 'id' and referenced column 'id' in foreign key
 *     constraint 'tf_wallpapers_settings_udiff_parent_fk' are incompatible.
 *     SQL: alter table `tf_wallpapers_settings` add constraint
 *          `tf_wallpapers_settings_udiff_parent_fk` foreign key (`account_id`, `id`)
 *          references `tf_wallpapers` (`account_id`, `id`) on delete cascade
 *     at src/Laravel/Migrations/2026_10_04_000900_add_update_differences_misc_family_foreign_keys.php:322
 *
 * Driver code 3780, read out of `$e->errorInfo[1]`. MySQL 8.4.11-0ubuntu0.26.04.1,
 * database `teleframe_wp3780`, engine InnoDB. The dial therefore never produced
 * a schema at all on the truth-track engine, and every MySQL test in the
 * project errored — the MySQL truth track had never once had a schema.
 *
 * The cause is TWO DIFFERENT WIDTHS FOR ONE KEY, IN ONE SHIPPED FILE, FIFTEEN
 * LINES APART. Read out of the executed schema of `information_schema.COLUMNS`:
 *
 *     tf_wallpapers            account_id bigint unsigned   id int
 *     tf_wallpapers_settings   account_id bigint unsigned   id bigint
 *
 * and both lines are in
 * `src/Laravel/Migrations/2026_09_29_000100_create_tf_mirror_gap_tables.php`:
 *
 *     line 163   Schema::create('tf_wallpapers', …)            $table->integer('id');
 *     line 177   Schema::create('tf_wallpapers_settings', …)   $table->bigInteger('id');
 *
 * It is integer WIDTH and not signedness: both legs are SIGNED (neither call
 * passes `unsigned: true`), so `signedness_agrees=yes` on the measured pair, and
 * MySQL still refuses it with 3780.
 *
 * ## WHY SQLITE COULD NOT SEE IT, AND WHY THAT IS THE LESSON
 *
 * `SQLiteGrammar` maps BOTH types to one spelling, so on SQLite the parent and
 * the child are indistinguishable:
 *
 *     vendor/laravel/framework/…/Schema/Grammars/SQLiteGrammar.php
 *         typeInteger(Fluent $column)  { return 'integer'; }
 *         typeBigInteger(Fluent $column) { return 'integer'; }
 *
 * This is precisely the class the plan names when it says SQLite cannot express
 * 3780. It only appears on a real InnoDB engine.
 *
 * **This is the SECOND family in a day with the same defect.**
 * `tf_dialog_filters.id` was `int` against `bigint` children and was fixed in
 * `2026_10_04_001100_widen_tf_dialog_filters_id_to_bigint.php`. That file is the
 * template, and this one follows its structure, its idempotence handling and its
 * reasoning.
 *
 * ## WHY THE STEM IS `000895` AND NOT `001300` — ORDER IS LOAD-BEARING HERE
 *
 * This is the one place where copying `001100` verbatim would have produced a
 * file that never executes, so the difference is stated rather than hidden.
 *
 * `001100` could take a late stem because the migration it followed, `000870`,
 * REFUSED the six relations it needed to widen for — `REFUSED` is a filter on
 * `000870`'s OWN loop, so `000870` skipped them, completed, and `001100` got its
 * turn. Nothing threw.
 *
 * `000900` does not refuse this relation. Its `REFUSED` is an EMPTY array
 * (`private const REFUSED = [];`), so it ATTEMPTS the wallpapers leg, and that
 * attempt is what raises 3780. A file sorting after `000900` is therefore
 * unreachable on a fresh install: the migration run aborts at `000900` and never
 * reaches the next file. A `001300` widen would look correct in review, pass
 * every SQLite test, and fix nothing on the engine that matters.
 *
 * So this file sorts BEFORE `000900` — `…_000895_…` < `…_000900_…` — and widens
 * the parent first. `000900` then finds `bigint` → `bigint` and its
 * `ALTER TABLE … ADD CONSTRAINT` is accepted by MySQL.
 *
 * MEASURED PROOF THAT THE ONE ALTER IS THE WHOLE DIFFERENCE, on MySQL
 * 8.4.11-0ubuntu0.26.04.1. The same dial, the same server, two runs:
 *
 *     000900 SKIPPED, parent left `int`   -> 269 tables, 467 foreign keys, no schema for 000900
 *     this file PRESENT, full dial        -> {"files":55,"applied":55,"tables":269,
 *                                            "fks":476,"fk_legs":794,
 *                                            "foreign_key_checks":1,
 *                                            "version":"8.4.11-0ubuntu0.26.04.1"}
 *
 * 55 files, not 53: lane `SVC-FK` landed `2026_10_04_001200_drop_false_sibling_foreign_keys.php`
 * on the same tree, so the dial is two migrations wider than the number the portability pins still
 * carry. `foreign_key_checks` and the version are READ BACK from the engine after the run, never
 * assumed.
 *
 * ## THE BITE, PROVEN IN BOTH DIRECTIONS ON A /tmp COPY
 *
 * The repo's migration file was NEVER edited to produce this. A copy of the directory was made
 * and mutated on the copy, then the copy's files were run in the copy's own sort order — which
 * is exactly the dial's apply order, so this file sits at index 51 and `000900` at 52:
 *
 *     MODE=present  (this file intact)     -> 55 applied, tf_wallpapers.id = bigint, FK legs = 2
 *     MODE=narrow   (ALTER neutralised)   -> 000900 raises 3780, id = int,     FK legs = 0
 *     MODE=absent   (file deleted)        -> 000900 raises 3780, id = int,     FK legs = 0
 *
 * The `narrow` and `present` rows differ by one ALTER and nothing else. The repo copy was verified
 * still to contain the live `bigInteger(self::KEY_COLUMN)->change()` call afterwards.
 *
 * ## THE WHOLE-SCHEMA SWEEP — ONE MISMATCH, AND IT IS THIS ONE
 *
 * Fixing only the family that happened to be exercised is how the NEXT one arrives, so the shape
 * was swept across the entire executed schema three independent ways:
 *
 *  1. DERIVED — `MirrorRelationPlan::singleParent()` gives 206 relations; every leg compared
 *     child-against-parent on the same connection. Mismatched: **1** — exactly this one. One
 *     further leg was skipped (`tf_messages_reply_markup_rows_buttons.parent_position`, where the
 *     column is absent on one side) and is recorded rather than treated as clean.
 *  2. DERIVATION-FREE — a plain `information_schema` join pairing any integer column with a
 *     same-named NARROWER column in a table whose name is a prefix of the child's. 3 pairs
 *     total: **1** is a key or FK leg (this defect); the other **2** are
 *     `tf_documents_thumbs.size` and `tf_documents_video_thumbs.size` against
 *     `tf_documents.size`, and both are ordinary payload columns — no key, no FK leg, and
 *     `information_schema.KEY_COLUMN_USAGE` confirms `size` is never a leg of any constraint. They
 *     cannot raise 3780, so they are reported and NOT widened.
 *  3. SIGNEDNESS — `account_id` is `bigint unsigned` on **266** tables and there is exactly
 *     ONE row for it, so no signedness mismatch exists on the tenant leg either.
 *
 * So the schema held exactly one key-width landmine and this file disarms it. The count is a
 * measurement of the executed schema, not an inference from the migration text.
 *
 * ## WHY THIS IS A NEW FILE AND NOT AN EDIT TO `2026_09_29_000100`
 *
 * The two files carrying the mismatched declarations are SHIPPED and COMMITTED:
 *
 *     $ git cat-file -e HEAD:src/Laravel/Migrations/2026_09_29_000100_create_tf_mirror_gap_tables.php
 *     $ git cat-file -e HEAD:src/Laravel/Migrations/2026_10_04_000900_add_update_differences_misc_family_foreign_keys.php
 *     -> both succeed on this tree
 *
 * (MEASURED, and the receipt is the HEAD commit: no `v*` tag carries either,
 * each checked with `git cat-file -e "$t:<path>"`.) An in-place edit would leave
 * every install that already ran them with the wrong schema, recorded as
 * applied, forever — the exact failure an additive migration exists to avoid.
 *
 * ## THE WIDENING IS THE SAFE DIRECTION, PROVEN BY AN INVENTORY THAT IS CLOSED
 *
 * A widening strands a row only if some column SOMEWHERE references this parent
 * and is NARROWER than the new parent. MEASURED on the executed schema, by three
 * independent queries, not by reading the create migration:
 *
 *  1. **Existing foreign keys into `tf_wallpapers` or `tf_wallpapers_settings`** —
 *     `SELECT … FROM information_schema.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_NAME
 *     IN ('tf_wallpapers','tf_wallpapers_settings')` returns **0 rows**. Nothing
 *     already depends on the column's current type.
 *  2. **Denormalised pointer columns** — `SELECT … WHERE COLUMN_NAME LIKE
 *     '%wallpaper%'` over EVERY column in EVERY table returns **0 rows**. There is
 *     no `wallpaper_id` anywhere in the schema, so there is no second route by
 *     which a table could reference this parent.
 *  3. **Every table in the family** — `WHERE TABLE_NAME LIKE 'tf_wallpapers%' AND
 *     COLUMN_NAME = 'id'` returns exactly **2** rows: `tf_wallpapers` (`int`) and
 *     `tf_wallpapers_settings` (`bigint`). There is no third table and no deeper
 *     depth, so the referencing set is CLOSED at one child.
 *
 * The child is ALREADY `bigint`. Widening the parent moves it TOWARD the child,
 * so nothing is left behind — the hazard a widening creates (a child that
 * resolves to no parent) cannot arise, because there is no narrower child to
 * leave behind. This is the same argument `001100` makes for
 * `tf_dialog_filters`, and it holds here for the same structural reason: the
 * parent is the NARROW side. **The narrowing direction is the one that strands
 * rows, and it is not taken** — not here, and not in `down()`.
 *
 * `int` → `bigint` is a strict SUPERSET (`int` domain [-2147483648, 2147483647]
 * ⊂ `bigint` domain [-9223372036854775808, 9223372036854775807]), so the ALTER
 * can neither truncate nor drop an existing value.
 *
 * ## WHY `(0,0)` CANNOT OCCUR FOR THIS PARENT — IT IS A DIFFERENT SHAPE
 *
 * D-021 established that an absent optional PEER is stored as `(0, 0)` rather
 * than omitted, which makes a `tf_peers` registry FK unusable. That reasoning
 * does NOT transfer here, and the difference is structural:
 *
 *  - the `(0,0)` sites are OPTIONAL POINTERS to another entity, where "absent"
 *    must be encoded somehow and `0` is the only available spelling;
 *  - `tf_wallpapers_settings.id` is NOT a pointer. It is the wallpaper's OWN
 *    Telegram id — the settings object carries no separate identifier, so the
 *    child's key IS the parent coordinate. This is stated at the model too:
 *    `src/Teleframe/Mirror/Models/TfWallpaperSettings.php:23-26` — *"1:1 FACT.
 *    The key is the wallPaper's own `id` — the settings object carries no separate
 *    identifier."*
 *
 * A child row therefore cannot have an `id` that names no wallpaper, because its
 * `id` IS the wallpaper's id. The `(0,0)` failure mode requires a column whose
 * value is INDEPENDENT of the parent's existence; this relation has no such
 * column. Additionally the batch is ordered parent-first before it is inserted
 * (`MirrorFactWriter::write()` → `parentFirst()`), and a re-delivered wallpaper
 * carries its settings field again, so the parent and the child are present or
 * absent TOGETHER by wire construction.
 *
 * ## WHY THIS FILE WIDENS ONLY, AND DOES NOT DECLARE THE CONSTRAINT
 *
 * Unlike `001100`, this file does NOT add the foreign key, and the difference is
 * deliberate. `001100` had to add its six because `000870` had REFUSED them. Here
 * `000900` has NOT refused this relation — its `REFUSED` is empty and it writes
 * the constraint itself. Once the parent is widened, `000900`'s own loop adds
 * `tf_wallpapers_settings_udiff_parent_fk` on the very next file, and it is
 * idempotent: `existingConstraint()` short-circuits a re-run.
 *
 * Declaring the constraint here as well would produce a SECOND copy of the same
 * logical constraint, with a different name and a different `down()` owner, and
 * `001100`'s own docblock records what that costs: a shared or parallel suffix
 * makes `down()` unable to tell whose constraints it holds, which is exactly how
 * `000700`'s `down()` once removed 271 constraints instead of 265. One owner per
 * constraint is the rule; `000900` owns this one.
 *
 * ## WHY SQLITE IS NOT THE GATE FOR THE WIDTH, BUT IS THE GATE FOR THE BITE
 *
 * sqlite cannot raise 3780 at all. What this file's SQLite gate proves is the
 * part sqlite CAN do: that the widen is executable and idempotent, that it is a
 * no-op on a driver that already reports both legs as `integer`, and that
 * `000900` afterwards lands the constraint. The MySQL lane owns the 3780.
 *
 * `down()` REFUSES on a driver that cannot record constraint names, exactly as
 * `000700`, `000870`, `000900` and `001100` do, rather than reporting a rollback
 * that changed nothing. It does NOT narrow the column back: `bigint -> int` is
 * the LOSSY direction, the mirror may by now hold ids above the int domain, and
 * narrowing a key is forbidden outright. A rollback that leaves the column wide
 * is honest; one that narrows is a data-loss hazard.
 */
return new class extends Migration
{
    /**
     * The table whose `id` this migration widens.
     *
     * It is the SECOND HALF of its own composite key `(account_id, id)`, and the
     * half that is wrong: `account_id` is `bigint unsigned` on all 266 tables
     * that carry it (MEASURED, `GROUP BY COLUMN_TYPE` over information_schema —
     * one row, `bigint unsigned`, 266 tables), so only `id` can be the mismatch.
     */
    private const NARROW_PARENT = 'tf_wallpapers';

    /** The column widened. */
    private const KEY_COLUMN = 'id';

    /**
     * The one table that references {@see NARROW_PARENT}.
     *
     * NOT a leg list and NOT a constraint target — this file declares no
     * constraint (see the class docblock). It is the DISCOVERY SENTINEL: the
     * widen runs only when a relation whose parent is {@see NARROW_PARENT} is
     * actually derived from the executed schema, so on a database that never got
     * that table the file is a no-op rather than an ALTER against nothing.
     *
     * The name is used as a PREFIX test because membership in a one-child family
     * is not derivable from a table name in any other way; the relation's own
     * parent is what is checked, never the name.
     */
    private const FAMILY_PREFIX = 'tf_wallpapers_';

    /**
     * MySQL's integer widths, for the direction decision, read off the engine's
     * own declared types rather than assumed.
     *
     * The widening direction is chosen by COMPARISON and refused when it is not
     * the safe one. `PARENT_WIDE` means the child is narrower than the parent,
     * which is the direction that STRANDS rows: widening the CHILD would leave
     * existing child values valid but would not bring them into the parent's
     * domain, so this file refuses rather than guessing. On the tree this file was
     * written against that branch is unreachable — see the sweep numbers in the
     * docblock — but it is a decision that must be MADE from the schema, not
     * assumed to be the convenient one.
     */
    private const INTEGER_WIDTHS = [
        'tinyint' => 1,
        'smallint' => 2,
        'mediumint' => 3,
        'int' => 4,
        'bigint' => 8,
    ];

    /**
     * Every integer-ish column type MySQL emits, mapped to the key in
     * {@see INTEGER_WIDTHS}.
     *
     * `tinyint(1)` is in the table because Laravel emits it for every boolean and
     * a boolean cannot be an FK leg here — but it must still be RECOGNISED, not
     * silently treated as unknown, or the comparison below would refuse a
     * relation it never actually examined.
     */
    private const TYPE_PREFIXES = ['tinyint' => 'tinyint', 'smallint' => 'smallint', 'mediumint' => 'mediumint', 'int' => 'int', 'bigint' => 'bigint'];

    public function up(): void
    {
        if (! Schema::hasTable(self::NARROW_PARENT)) {
            return;
        }

        $relations = $this->relationsIntoParent();
        if ($relations === []) {
            return;
        }

        $this->assertTheWideningIsTheSafeDirection($relations);

        // STEP 1 and only step. Widen the parent, BEFORE `000900` writes a
        // constraint that references it. A FOREIGN KEY is checked against the
        // parent's type as it stands when the constraint is CREATED, so a widen
        // that runs after `000900` is a widen that never runs at all — this is
        // why the file's stem sorts before it.
        Schema::table(self::NARROW_PARENT, function (Blueprint $blueprint): void {
            $blueprint->bigInteger(self::KEY_COLUMN)->change();
        });
    }

    public function down(): void
    {
        // NOTHING TO DO, deliberately, and the reason is a safety rule rather than
        // an oversight.
        //
        // This file declares no constraint of its own — `000900` owns
        // `tf_wallpapers_settings_udiff_parent_fk` — so there is nothing of this
        // migration's to drop. And the column is NOT narrowed: `bigint -> int` is
        // the LOSSY direction, the mirror may by now hold ids above the int
        // domain, and narrowing a key is forbidden outright. Narrowing on rollback
        // would make a `migrate:rollback` a data-loss command.
        //
        // Rolling back the CONSTRAINT is `2026_10_04_000900`'s `down()` to do, and
        // it can only do it on a driver that records constraint names — MySQL.
    }

    /**
     * Every derived relation whose parent IS {@see NARROW_PARENT}.
     *
     * DISCOVERED, never transcribed: the edges come from
     * `MirrorRelationPlan::fromConnection()` — reflection over the EXECUTED
     * schema, the same derivation `000900` and the anti-rot gate's RED list are
     * built from, so this file and the gate cannot disagree about which relations
     * exist or what their legs are. A hand-written table list would be a second
     * copy of the schema, and a second copy drifts.
     *
     * The prefix is applied as a cheap pre-filter only; membership is then
     * decided by the relation's own `parent`, so a table that merely shares a
     * prefix is never admitted on its name.
     *
     * @return list<array{child:string, parent:string, columns:list<string>, reason:string}>
     */
    private function relationsIntoParent(): array
    {
        $relations = [];

        foreach (MirrorRelationPlan::fromConnection(Schema::getConnection())->singleParent() as $relation) {
            if ($relation['parent'] !== self::NARROW_PARENT) {
                continue;
            }

            // The key half of the leg must be one this file actually widens,
            // otherwise the relation is not the defect being fixed.
            if (! in_array(self::KEY_COLUMN, $relation['columns'], true)) {
                continue;
            }

            $relations[] = $relation;
        }

        return $relations;
    }

    /**
     * REFUSE the widen when the schema says the child is the NARROWER side.
     *
     * Widening is safe when the parent is the narrow one: every child already
     * carries the wider type, so nothing is left behind. When the parent is the
     * WIDER one, widening it is a no-op that hides the real defect, and the
     * direction that would actually be needed — widening the child — can strand
     * rows, so this method stops instead of choosing.
     *
     * The comparison is made on the DECLARED column type read back from the
     * engine, so it is a measurement of the schema and not of this file's intent.
     */
    private function assertTheWideningIsTheSafeDirection(array $relations): void
    {
        $connection = Schema::getConnection();

        $parentWidth = $this->integerWidthOf($connection, self::NARROW_PARENT, self::KEY_COLUMN);
        if ($parentWidth === null) {
            return;
        }

        foreach ($relations as $relation) {
            $childWidth = $this->integerWidthOf($connection, $relation['child'], self::KEY_COLUMN);
            if ($childWidth === null) {
                continue;
            }

            // Child at least as wide as the parent: the parent IS the narrow
            // side, so widening it moves the parent TOWARD the child and leaves
            // nothing behind. This is the safe direction and the one taken.
            if ($childWidth >= $parentWidth) {
                continue;
            }

            throw new RuntimeException(sprintf(
                'refusing to widen %s.%s: the parent already declares %d bytes while its referencing '
                .'table %s declares only %d. Widening the parent is then a no-op that HIDES the real '
                .'defect, and the direction that would actually be needed runs on the CHILD — which '
                .'narrows nothing but must be decided against the child\'s own rows, not guessed here. '
                .'The widths were read from the engine, not assumed.',
                self::NARROW_PARENT,
                self::KEY_COLUMN,
                $parentWidth,
                $relation['child'],
                $childWidth,
            ));
        }
    }

    /**
     * The declared width, in bytes, of an integer column — or NULL when the
     * column is absent or its type is not an integer at all.
     *
     * NULL is deliberately "not comparable" rather than 0: a column this cannot
     * read must not be silently treated as the widest, which would let a real
     * mismatch through the comparison above.
     */
    private function integerWidthOf(mixed $connection, string $table, string $column): ?int
    {
        foreach ($connection->getSchemaBuilder()->getColumns($table) as $definition) {
            if ((string) ($definition['name'] ?? '') !== $column) {
                continue;
            }

            $type = strtolower((string) ($definition['type_name'] ?? $definition['type'] ?? ''));

            foreach (self::TYPE_PREFIXES as $needle => $key) {
                // `tinyint(1)` and friends carry a display width; the prefix is
                // what identifies the storage class.
                if (str_starts_with($type, $needle)) {
                    return self::INTEGER_WIDTHS[$key];
                }
            }

            return null;
        }

        return null;
    }
};
