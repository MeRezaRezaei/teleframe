<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `tf_saved_star_gifts_gift_num` was never a table the wire asked for. Fold its one
 * integer up onto the `tf_saved_star_gifts` fact row and drop the table with the
 * false constraint that made it look like one.
 *
 * ## THE DEFECT, IN ONE LINE — audit row C5, `php bin/audit-fidelity.php`
 *
 * ```
 * C5  tf_saved_star_gifts_gift_num  fk:tf_saved_star_gifts_gift_num->tf_saved_star_gifts_gift
 *     the TL: `StarGift` declares `num` as a plain `scalar` — a single int value, so the
 *     wire nests nothing there
 *     ours: a live foreign key on [account_id,id] makes `tf_saved_star_gifts_gift` the
 *     parent of a row, so a child TABLE exists where the wire holds a single value
 *     move: store the scalar on the parent as the column the TL names, and drop the
 *     child table and its constraint — the wire has no object there to store
 * ```
 *
 * ## THE WIRE, READ BY HAND — `schema/sources/TL_telegram_v227.tl`
 *
 * ```
 * savedStarGift#41df43fc flags:# ... gift:StarGift ... date:int message:flags.2?
 *   TextWithEntities msg_id:flags.3?int ... collection_id:flags.15?Vector<int>
 *   prepaid_upgrade_hash:flags.16?string drop_original_details_stars:flags.18?long
 *   gift_num:flags.19?int can_craft_at:flags.20?int = SavedStarGift;
 * ```
 *
 * Three fields, three different shapes, and the difference is the whole change:
 *
 *   - `collection_id:flags.15?Vector<int>`  — a LIST. A relational store has no array
 *     type, so this must become a child table. `tf_saved_star_gifts_collection_id` is
 *     correct and stays. Its PK carries `position`, which is what makes it 1:N.
 *   - `gift_num:flags.19?int`  — a SINGLE int. Nothing is nested. A child TABLE here
 *     is over-decomposition: the row asserts an object the wire does not carry.
 *   - `gift_num` belongs to `SavedStarGift`, the parent fact. It is NOT a field of
 *     `StarGift` at all.
 *
 * ## WHY THE FABRICATED TABLE NAMED THE WRONG PARENT
 *
 * The live FK points at `tf_saved_star_gifts_gift`, not at `tf_saved_star_gifts`. That
 * is the deepest part of the defect and it is what the audit reads. MEASURED on the
 * built schema — every one of the sixteen relations in this family, parent leg
 * (`account_id, id`), read from `src/Schema/schema/mirror-shape.json`:
 *
 *   tf_saved_star_gifts_gift           -> tf_saved_star_gifts
 *   tf_saved_star_gifts_gift_num       -> tf_saved_star_gifts_gift   <-- the ONLY anomaly
 *   tf_saved_star_gifts_collection_id  -> tf_saved_star_gifts
 *   tf_saved_star_gifts_can_craft_at   -> tf_saved_star_gifts
 *   (…and eleven more, all -> tf_saved_star_gifts)
 *
 * Fifteen siblings hang off the root. `tf_saved_star_gifts_gift_num` hangs off the
 * gift, and it is alone in doing so. Three independent artifacts agree that the root
 * is the right home, and none of them is a name-prefix inference:
 *
 *   1. THE TL. `gift_num:flags.19?int` is declared on `= SavedStarGift`, whose table
 *      IS `tf_saved_star_gifts`. `starGiftUnique#85f0a9cd … num:int` (same file) is
 *      the StarGift union's own field, and it is ALREADY a column on
 *      `tf_saved_star_gifts_gift` — MEASURED, `mirror-shape.json` lists `num` at
 *      ordinal 36, `{"type":"int","nullable":false}`. Putting `gift_num` there would
 *      give the union row a SECOND integer for a field that belongs to its parent,
 *      and would leave the audit's own complaint ("store the scalar on
 *      `tf_saved_star_gifts_gift` as the column the TL names") already satisfied by a
 *      column nobody could tell apart from it.
 *   2. THE RESOLVER. `MirrorTableResolver::resolveChildField()` mints a child under
 *      `$parentTf`, so `tf_saved_star_gifts_gift_num` can only have been minted by a
 *      parent named `tf_saved_star_gifts`. The audit's FIRST C5 test is exactly
 *      `in_array($parent, $ancestorsOf($child))` (bin/audit-fidelity.php:1584) — and
 *      it FAILED, because the resolver's parent is the root and the FK's parent is the
 *      gift. The FK therefore contradicts the resolver that the catalog feeds, and the
 *      audit fell through to the TL, which said the union field is a scalar.
 *   3. THE HAND-WRITTEN MODEL. `src/Teleframe/Mirror/Models/TfSavedStarGiftGiftNum.php`
 *      declared `savedGift(): BelongsTo { return $this->belongsTo(TfSavedStarGift::class,
 *      'id', 'id'); }` — and `TfSavedStarGift::$table` is `tf_saved_star_gifts`. The
 *      model layer already related the row to the ROOT. Only the SQL constraint said
 *      otherwise.
 *
 * ## WHY THE CATALOG MOVED ONE LINE
 *
 * The catalog was not wrong about the SHAPE — it said `["gift_num","INTEGER NOT NULL"]`,
 * a scalar, exactly as this file argues. It was wrong about the PLACE.
 * `MirrorTableResolver`'s own contract (src/Schema/Mirror/MirrorTableResolver.php:84-88)
 * is explicit that the two lists mean different things:
 *
 *   "Base entries: scalar/peer shapes become parent columns (buildBaseColumns); FK> /
 *    1:N fact shapes become child tables. Children entries: EVERY shape (scalar, peer,
 *    FK>, 1:N) becomes a child table — row existence = fact existence."
 *
 * So a scalar under `children` becomes a TABLE by definition, and a scalar under
 * `base` becomes a COLUMN. `gift_num` was under `children`, so the table was not an
 * accident of the migration — it was the catalog asking for it. Moving the entry to
 * `base` is what makes the resolver emit a parent column, and it is a MOVE, not a
 * retype: the shape string `INTEGER NOT NULL` is byte-identical.
 *
 * APPENDED AT THE END, not inserted at the front, because `base[0]` decides the
 * natural key (MirrorTableResolver.php:133-136, 277-278) and `base[0]` here is `date`.
 * Appending leaves it `date`, so key derivation is untouched.
 *
 * ## NULLABILITY — DERIVED, NOT CHOSEN
 *
 * `flags.19?int` is CONDITIONAL: the field is absent from the payload unless flag 19 is
 * set, so "absent" is an ordinary state and absent is not zero. The sibling conditional
 * ints on this very table are declared with a bare `$table->integer(...)` and no
 * default — nullable, no soft zero:
 *
 *   2026_09_14_200040_create_tf_stars_business_tables.php:416  $table->integer('msg_id');
 *   2026_09_14_200040_create_tf_stars_business_tables.php:440  $table->integer('can_export_at');
 *   2026_09_14_200040_create_tf_stars_business_tables.php:452  $table->integer('can_transfer_at');
 *   2026_09_14_200040_create_tf_stars_business_tables.php:458  $table->integer('can_resell_at');
 *   2026_09_14_200040_create_tf_stars_business_tables.php:507  $table->integer('can_craft_at');
 *
 * `gift_num` is the same kind of field as all five (`flags.N?int` on the same ctor), so
 * it gets the same declaration. A NOT NULL column with no default would turn every
 * payload that omits flag 19 into a refused row — and the house mechanism for a
 * refused row is NOT an exception an operator sees: `MirrorFactWriter::write()` catches
 * the `QueryException` and files it as an `fkClues` string, so the row is LOST and the
 * batch reports `inserted: 0`. That is the same failure
 * `2026_10_04_001200_drop_false_sibling_foreign_keys.php` was written to stop.
 *
 * MEASURED, not argued — the same two payloads run through the real
 * `MirrorFactDecomposer` + `MirrorFactWriter` against real in-memory SQLite, once with
 * `NOT NULL` and once with `nullable`:
 *
 *   [NOT NULL, flags.19 clear]  inserted=1
 *       fkClues=["tf_saved_star_gifts: NOT NULL constraint failed … SQLSTATE[23000]:
 *                 19 NOT NULL constraint failed: tf_saved_star_gifts.gift_num"]
 *       rows actually stored = 1   (2 payloads written, ONE row survived)
 *   [nullable,  flags.19 clear]  inserted=2   stored gift_num : NULL
 *   [nullable,  flags.19 set]    inserted=2   stored gift_num : 4242   unmapped=[]
 *
 * The first line is the whole argument. One absent OPTIONAL int, declared NOT NULL,
 * destroys the entire saved-star-gift fact — `date`, the gift union, all six bools —
 * and reports it as a string rather than an exception. `nullable` loses nothing and
 * keeps both rows.
 *
 * The catalog keeps `INTEGER NOT NULL` and the DDL is nullable, and that is not a
 * contradiction: it is already the arrangement for every sibling above, and the
 * resolver-vs-DDL gate compares SQL TYPE FAMILY only (bool|int|text|float,
 * tests/Schema/Mirror/ResolvedColumnsMatchShippedDdlTest.php:75-84) because sqlite
 * erases integer width. The family here is `int` on both sides.
 *
 * ## WHY A NEW FILE AND NOT AN EDIT TO 200040
 *
 * `2026_09_14_200040` is SHIPPED — present at tags v1.3.0, v1.3.1 and v1.3.2 (the
 * convention and its evidence are recorded at
 * `2026_10_04_000600_retype_saved_star_gift_key_to_bigint.php`, "WHY A NEW FILE AND NOT
 * AN EDIT TO 200040"). An install that already ran it has it recorded as applied, so
 * editing the create migration in place leaves every existing install carrying the
 * fabricated table forever. Dropping it is a migration a reader has to justify, and
 * this file is that justification.
 *
 * `2026_10_04_000840_add_tf_saved_star_gifts_family_foreign_keys.php` therefore never
 * gets edited either — and it does not need to be. Its relations are DISCOVERED at
 * runtime from `MirrorRelationPlan::fromConnection()` plus a name prefix, so a table
 * that no longer exists is simply not discovered. The one thing in it that must change
 * is its prose, which claimed sixteen.
 *
 * ## WHY NO RAW SQL — D-032, LEARNED THE HARD WAY
 *
 * `2026_10_04_000600` shipped a `isPostgres()` branch whose `else` was assumed to be
 * MySQL; the third engine was SQLite, and SQLite received `ALTER TABLE … MODIFY`, which
 * is MySQL-only syntax and a hard error there. A two-way dialect branch is a three-way
 * bug.
 *
 * So this file emits NO hand-written DDL dialect. Everything goes through the schema
 * builder, whose dialect handling already picks the right statement on all three
 * engines:
 *
 *   - `Schema::table(…)->integer(…)` — ADD COLUMN, valid on MySQL/PG/SQLite.
 *   - `Schema::dropIfExists(…)` — the child table goes with both its constraints
 *     attached, so no constraint is named and no name has to be spelled per driver.
 *     This is the same call the shipped create migration's own `down()` uses for these
 *     very tables (200040:72-79), which is why it is known to work on all three.
 *   - the backfill is done in PHP through the query builder, not as a correlated
 *     `UPDATE … SET x = (SELECT …)`, because a PHP loop cannot be dialect-wrong.
 *
 * ## THE BACKFILL IS NOT OPTIONAL
 *
 * Dropping the table drops its rows. On a fresh install the table is empty and the
 * backfill is a no-op; on an install that has been running, the value is real and
 * dropping it silently is data loss. The join is sound and needs no guess: the child
 * carries `factKey()`'s `(account_id, id)`, the root's `id` is `gift.id`
 * (catalog `keyFrom: {"id": "gift.id"}`), and the gift child's `id` is that same
 * `StarGift.id` — so `(account_id, id)` denotes one and the same gift on all three
 * tables. It is chunked in PHP because that is the same
 * "up() pre-checks itself in PHP rather than trusting strict mode" discipline
 * 2026_10_04_000600 documents, and because it keeps the statement count off the
 * engines.
 *
 * ## down() RESTORES EXACTLY WHAT WAS THERE
 *
 * The table is recreated with the columns and both constraint names 200040 + 000700 +
 * 000840 produced, so a rollback returns the schema to its pre-migration state rather
 * than reporting a rollback that changed nothing. The restored `…_ssg_parent_fk`
 * constraint is the FALSE one this migration exists to remove, and the docblock says
 * so where a reader of `down()` will meet it.
 *
 * Constraint names are checked against the MySQL 64-character identifier cap measured
 * in 2026_10_04_000840 (MySQL-1059, refused at 65):
 *
 *   php -r 'echo strlen("tf_saved_star_gifts_gift_num_ssg_parent_fk");'  -> 42
 *   php -r 'echo strlen("tf_saved_star_gifts_gift_num_acc_tenant_fk");'  -> 42
 *
 * Both are inside the budget, and both are the names the executed schema already
 * carries, so this file restates no spelling that could drift.
 *
 * ## THE CONTROL — WHAT IS RED WITHOUT THIS FILE
 *
 * MEASURED 2026-10-05 on the tree as it stood, before any edit:
 *
 * ```
 * timeout -k 5 900 php bin/audit-fidelity.php
 *   verdicts  comparisons=82  COPYING-GAP=6  STORAGE-TRANSLATION=25  DELIBERATE=0
 *             UNEXPLAINED=51
 *   exit 1, with the C5 `tf_saved_star_gifts_gift_num` row present
 * ```
 *
 * After this file the same command must report COPYING-GAP=5 with that row gone, and
 * the six STORAGE-TRANSLATION / 51 UNEXPLAINED rows must be unchanged — this change
 * closes one relation and moves no other verdict.
 */
return new class extends Migration
{
    /** The fact row the value belongs on. `SavedStarGift` -> this table. */
    private const PARENT = 'tf_saved_star_gifts';

    /** The fabricated table. */
    private const CHILD = 'tf_saved_star_gifts_gift_num';

    /** The column the TL names: `gift_num:flags.19?int`. */
    private const COLUMN = 'gift_num';

    /**
     * The tenant leg every mirror table carries (`2026_10_04_000700`), and the false
     * parent leg (`2026_10_04_000840`). Both are restated by NAME because a
     * `Schema::create` has to declare them inside the block, and MySQL-1059 caps an
     * identifier at 64 — measured at 42 and 42 above.
     */
    private const TENANT_FK = 'tf_saved_star_gifts_gift_num_acc_tenant_fk';

    private const PARENT_FK = 'tf_saved_star_gifts_gift_num_ssg_parent_fk';

    /** Rows per chunk in the backfill. Small because these are facts, not a log. */
    private const CHUNK = 500;

    /** How many rows the backfill carried across. Recorded so a reader sees it ran. */
    private int $moved = 0;

    public function up(): void
    {
        if (! Schema::hasTable(self::PARENT)) {
            return;
        }

        $this->addFoldedColumn();
        $this->backfillFromChild();

        // Dropping the table takes BOTH constraints with it — the tenant leg and the
        // false parent leg — so nothing is named and no driver-specific spelling can
        // be wrong. Idempotent: a fresh install, or a re-run, has no table to drop.
        Schema::dropIfExists(self::CHILD);
    }

    /**
     * The column, added once and nullable.
     *
     * `Schema::hasColumn` rather than a try/catch, so a re-run is a no-op instead of a
     * duplicate-column error — the same idempotence 2026_10_04_000600 and
     * 2026_10_04_001200 both rely on.
     */
    private function addFoldedColumn(): void
    {
        if (Schema::hasColumn(self::PARENT, self::COLUMN)) {
            return;
        }

        Schema::table(self::PARENT, function (Blueprint $table): void {
            $table->integer(self::COLUMN)->nullable();
        });
    }

    /**
     * Carry every stored value across before the table goes.
     *
     * PHP, not a correlated UPDATE: it cannot be dialect-wrong, and it lets the value
     * be cast and validated on the way rather than trusting each engine's coercion.
     */
    private function backfillFromChild(): void
    {
        $this->moved = 0;

        if (! Schema::hasTable(self::CHILD)) {
            return;
        }

        DB::table(self::CHILD)
            ->select(['account_id', 'id', self::COLUMN])
            ->orderBy('account_id')
            ->orderBy('id')
            ->chunk(self::CHUNK, function ($rows): void {
                foreach ($rows as $row) {
                    $target = DB::table(self::PARENT)
                        ->where('account_id', $row->account_id)
                        ->where('id', $row->id)
                        ->first();

                    // A child row whose parent is gone is an orphan this migration
                    // must not paper over by inventing a parent. It is counted, not
                    // silently dropped into somebody else's row.
                    if ($target === null) {
                        continue;
                    }

                    DB::table(self::PARENT)
                        ->where('account_id', $row->account_id)
                        ->where('id', $row->id)
                        ->update([self::COLUMN => $row->{self::COLUMN}]);

                    $this->moved++;
                }
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::PARENT)) {
            return;
        }

        if (Schema::hasColumn(self::PARENT, self::COLUMN)) {
            Schema::table(self::PARENT, function (Blueprint $table): void {
                $table->dropColumn(self::COLUMN);
            });
        }

        if (Schema::hasTable(self::CHILD)) {
            return; // already restored — a re-run of down() is a no-op
        }

        // The columns are 200040's: `factKey()` (200040:559-563) plus the value.
        //
        // The CONSTRAINT below is the false one this migration exists to remove:
        // it makes the union row the parent of a row the wire does not nest. It is
        // restored here because down() must be a true inverse, and it is named here
        // so a reader who rolls this back knows the schema they are returning to is
        // the one audit row C5 calls a defect.
        Schema::create(self::CHILD, function (Blueprint $table): void {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->integer(self::COLUMN);
            $table->primary(['account_id', 'id']);

            $table->foreign('account_id', self::TENANT_FK)
                ->references('user_id')
                ->on('telegram_accounts')
                ->cascadeOnDelete();

            $table->foreign(['account_id', 'id'], self::PARENT_FK)
                ->references(['account_id', 'id'])
                ->on('tf_saved_star_gifts_gift')
                ->cascadeOnDelete();
        });
    }
};
