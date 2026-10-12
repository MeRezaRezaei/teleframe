<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `tf_bot_infos_description` was a table the wire never asked for, and it was
 * also standing BETWEEN two tables that do exist. Fold the scalar onto the
 * `tf_bot_infos` fact row as the column the TL names, re-parent the two genuine
 * unions it was holding hostage, and drop it.
 *
 * This is the FOURTH instance of the fold family, and it follows
 * `2026_10_10_000100_fold_update_scalar_children_onto_the_update_fact.php`,
 * `2026_10_08_000100_fold_channel_participant_scalars_onto_the_participant_fact.php`
 * and `2026_10_05_000100_fold_saved_star_gift_num_onto_the_saved_gift_fact.php`.
 * Where those three folded a value and dropped a table, this one additionally
 * moves two CONSTRAINTS — which is the whole of the extra damage here.
 *
 * ## THE TL, VERBATIM — `schema/sources/TL_telegram_v227.tl:697`
 *
 * ```
 * botInfo#4d8a0299 flags:# has_preview_medias:flags.6?true user_id:flags.0?long description:flags.1?string description_photo:flags.4?Photo description_document:flags.5?Document commands:flags.2?Vector<BotCommand> menu_button:flags.3?BotMenuButton privacy_policy_url:flags.7?string app_settings:flags.8?BotAppSettings verifier_settings:flags.9?BotVerifierSettings = BotInfo;
 * ```
 *
 * Verified with this repo's own parser rather than read by eye, because the
 * whole finding turns on the three params being of DIFFERENT kinds:
 *
 * ```
 * php -r 'require "vendor/autoload.php";
 *   $s = MeRezaRezaei\Teleframe\Schema\Generator\TlParser::parseFile("schema/sources/TL_telegram_v227.tl");
 *   foreach ($s->types()["BotInfo"]->constructors() as $c) {
 *     foreach (["description","description_photo","description_document"] as $f) {
 *       $p = $c->params()[$f] ?? null;
 *       if ($p) printf("%-20s kind=%-6s base=%-10s cond=%s\n", $f, $p->kind(), $p->baseType(), json_encode($p->condition()));
 *     }
 *   }'
 * ```
 *
 * ```
 * description         kind=scalar base=string    cond=["flags",1]
 * description_photo   kind=ref    base=Photo     cond=["flags",4]
 * description_document kind=ref    base=Document  cond=["flags",5]
 * ```
 *
 * ## THE DEFECT, IN ONE LINE
 *
 * `description` is a plain `flags.1?string` SCALAR, so a table that exists only
 * to hold it is a fabricated decomposition of that scalar — and, worse, the two
 * GENUINE unions `description_photo:Photo` / `description_document:Document`
 * were attached to that fabricated table instead of to `tf_bot_infos`, where the
 * catalog declares them.
 *
 * The catalog says so on its face. `src/Schema/schema/mirror-catalog.json`,
 * entry `tf_bot_infos`, `children` array — READ ONLY, not edited by this file:
 *
 * ```
 * ["user_id",              "BIGINT NOT NULL"]
 * ["description",          "TEXT NOT NULL"]
 * ["description_photo",    "FK→Photo"]
 * ["description_document", "FK→Document"]
 * ```
 *
 * `description_photo` / `description_document` are declared as children of
 * `tf_bot_infos`, NOT of any `description` object. The shipped DDL disagrees
 * with the catalog that generated it, and the shipped DDL is what ran.
 *
 * ## WHY THE FKs LANDED ON THE WRONG TABLE — A NAME-PREFIX COLLISION, MEASURED
 *
 * `MeRezaRezaei\Teleframe\Schema\Mirror\MirrorRelationPlan::reflect()` picks a
 * child's parent by LONGEST NAME PREFIX (`src/Schema/Mirror/MirrorRelationPlan.php`,
 * the `$parents` loop): it splits the child's table name on `_` and walks the
 * prefixes from the longest to the shortest, taking the first candidate that is
 * a real table whose PRIMARY KEY is contained in the child's.
 *
 * For `tf_bot_infos_description_photo` the candidate order is:
 *
 * ```
 * tf_bot_infos_description_document   (not a prefix)
 * tf_bot_infos_description            <-- MATCHES, and the loop stops here
 * tf_bot_infos
 * ```
 *
 * `tf_bot_infos_description` matches on the very first attempt, so
 * `tf_bot_infos` is NEVER TRIED. The decomposition is faithful in intent — the
 * composer emitted a child table per field name — and the NAME it emitted
 * (`description_photo`, from the field name `description_photo`) collides with
 * the fabricated `description` table's stem. Nothing about the composition was
 * wrong; the resolution step that attaches a name to a parent was.
 *
 * This is the same shape of failure as the four audit C5 rows the three earlier
 * folds closed, one level deeper: here the fabricated table did not merely
 * assert an object the wire does not nest, it CAPTURED two objects that the
 * wire does nest, and put them under the wrong parent.
 *
 * ## WHAT `up()` DOES, AND WHY IT IS ORDERED THE WAY IT IS
 *
 *  1. Guard on `tf_bot_infos` — a dial that never ran has nothing to fold.
 *  2. Add `description` to `tf_bot_infos`, `Schema::hasColumn`-guarded.
 *  3. BACKFILL from `tf_bot_infos_description`, BEFORE the drop.
 *  4. Re-parent the two children's FKs onto `tf_bot_infos` — BEFORE the drop,
 *     because the current FK is `cascadeOnDelete()` and dropping
 *     `tf_bot_infos_description` while it is still the parent would DELETE the
 *     photo and document rows along with it.
 *  5. Drop the fabricated table, as a SEPARATE LITERAL call.
 *
 * Step 4 before step 5 is the load-bearing ordering in this file. Reversing
 * those two lines is a silent data loss, not a failure: MySQL cascades the
 * child rows away with the parent and the migration reports success.
 *
 * ## NULLABILITY AND THE TEXT DEFAULT — THE HOUSE PRECEDENT, NOT A NEW RULE
 *
 * `description` is `flags.1?string`: absent on most `botInfo` values, so a
 * single column has to carry "this botInfo declares no description". The house
 * answer for a TEXT column is already shipped on this very family —
 * `2026_09_14_200041_create_tf_bots_tables.php`, `tf_bot_apps`:
 *
 * ```
 * $table->text('short_name')->default(DB::raw("('')"));
 * $table->text('title')->default(DB::raw("('')"));
 * $table->text('description')->default(DB::raw("('')"));
 * ```
 *
 * which is also exactly what the catalog asks for (`"TEXT NOT NULL"` — Laravel's
 * `text()` is NOT NULL by default, and `DB::raw("('')")` is MySQL 8.0.13+'s
 * expression default for a TEXT column; on PostgreSQL and SQLite the same call
 * produces their own valid `DEFAULT ''`). The folded column follows that
 * declaration rather than inventing a second convention.
 *
 * ## WHY NO RAW SQL — D-032, LEARNED THE HARD WAY TWICE
 *
 * `2026_10_04_000600` shipped a two-way `isPostgres()` branch whose `else` was
 * assumed to be MySQL; the third engine was SQLite, and SQLite received
 * `ALTER TABLE … MODIFY`, which is MySQL-only. A two-way dialect branch is a
 * three-way bug. So this file emits NO hand-written DDL dialect:
 *
 *   - `Schema::hasTable()` / `Schema::hasColumn()` — guards, not DDL.
 *   - `Schema::table(…)->text(…)->default(DB::raw("('')"))` — ADD COLUMN,
 *     valid on MySQL/PG/SQLite.
 *   - `Schema::table(…)->dropForeign($name)` and `Schema::table(…)->foreign(…)` —
 *     both ends of the re-parenting go through the grammar, so no constraint name
 *     is spelled per driver and no `ALTER TABLE … DROP FOREIGN KEY` is
 *     hand-written.
 *   - the backfill runs in PHP through the query builder, because a PHP loop
 *     cannot be dialect-wrong.
 *   - `Schema::dropIfExists(…)` — the drop, once, on its own.
 *
 * ## WHY THE DROP IS A LITERAL AND NOT A LOOP
 *
 * Both count reconciliations read the migration TEXT:
 * `CuratedDialMigratesOnMysqlTest::tenantFksRetiredAfterThePin()` matches
 * `/Schema::drop(?:IfExists)?\('(tf_[a-z0-9_]+)'\)/` and
 * `/Schema::drop(?:IfExists)?\(self::[A-Z_]+\)/`, and the SQLite twin
 * (`TenantAccountForeignKeyTest::tenantTablesRetiredAfterThePin()`) counts
 * generically over the same shapes. A `foreach (self::TABLES as $t)
 * Schema::dropIfExists($t);` matches NEITHER pattern — the first regex needs a
 * quoted literal, the second needs `self::CONST` — so both helpers would
 * silently count 0 retirements, the two floors would disagree with the schema,
 * and the error would surface as a MySQL-track failure in a file this one never
 * touches. This repo has already been burned by that twice.
 *
 * ## THE BACKFILL IS NOT OPTIONAL
 *
 * Dropping a table drops its rows. On a fresh install it is empty and the
 * backfill is a no-op; on an install that has been running, `description` is a
 * real string and dropping it silently is data loss. The join needs no guess:
 * the child carries the parent's own `(account_id, id)` PRIMARY KEY,
 * byte-for-byte, so it denotes one and the same botInfo on both tables.
 *
 * Orphans (a child row whose parent is gone) are SKIPPED, not papered over by
 * inventing a parent row — the same rule `2026_10_10_000100` follows.
 *
 * ## CONSTRAINT NAMES, AND WHY THESE TWO FIT UNDER MYSQL's CAP
 *
 * The name is `2026_10_04_000850`'s own suffix, `{child}_bot_parent_fk`, so the
 * re-parenting reuses the identical name and `down()` restores the identical
 * one. MySQL caps an identifier at exactly 64 characters (error 1059):
 *
 * ```
 * php -r 'echo strlen("tf_bot_infos_description_photo_bot_parent_fk");'
 *     -> 44
 * php -r 'echo strlen("tf_bot_infos_description_document_bot_parent_fk");'
 *     -> 47
 * ```
 *
 * Both fit. `ON DELETE CASCADE` is kept, not chosen again here: a botInfo's
 * decomposed unions have no life outside it, which is the family-wide rule
 * `2026_10_04_000850` records.
 *
 * ## WHY A NEW FILE AND NOT AN EDIT TO 200041
 *
 * `2026_09_14_200041_create_tf_bots_tables.php` is SHIPPED HISTORY and is not
 * edited. An install that already ran it has the fabricated table recorded as
 * applied forever, and its two children carry the mis-parented constraint
 * forever. This file is the change, in the same shape as the folds before it.
 *
 * ## WHY sqlite IS NOT THE GATE FOR THE RE-PARENTING
 *
 * SQLite cannot raise 3780 (FK signedness), 6125 (parent key not unique), 1059
 * (name over 64 chars) or 1101 (TEXT NOT NULL DEFAULT '') at all, and all four
 * of those are live here — this file issues a composite FK, adds a TEXT default
 * and creates two constraint names. The gate that proves the TARGET engine
 * accepts it is the MySQL lane, not a sqlite run.
 *
 * ## down() RESTORES EXACTLY WHAT WAS THERE — AND THE VALUES COME BACK TOO
 *
 * `tf_bot_infos_description` is recreated with `2026_09_14_200041`'s own three
 * columns and PRIMARY KEY, its ROWS are copied back off the fact, the two
 * children's constraints are pointed at it, and the folded `description` column
 * is dropped.
 *
 * ### THE ROWS ARE NOT OPTIONAL, AND THIS WAS MEASURED, NOT ASSUMED
 *
 * The order below is forced by MySQL error 1452, and it was found by running
 * the rollback rather than by reasoning about it. Re-creating the table EMPTY
 * and then re-pointing `tf_bot_infos_description_photo_bot_parent_fk` at it is
 * refused:
 *
 * ```
 * Integrity constraint violation: 1452 Cannot add or update a child row: a
 * foreign key constraint fails
 * (`teleframe_g2_botfold`.`#sql-85d_5b7a5`,
 *  CONSTRAINT `tf_bot_infos_description_photo_bot_parent_fk`
 *  FOREIGN KEY (`account_id`, `id`) REFERENCES `tf_bot_infos_description` …)
 * ```
 *
 * because the child rows are still there — `up()` moved the CONSTRAINT, never
 * the children — and the parent they are being handed back to has no rows. A
 * rollback that creates the table empty therefore cannot restore the constraint
 * at all, and the honest options are to fail the rollback or to refuse to
 * restore it. Copying the values across first is the third option and the only
 * one that makes `down()` a true inverse: the schema comes back AND the data
 * comes back.
 *
 * So `restoreChildRows()` runs between the `CREATE` and the re-parenting. It is
 * the same value in the same `(account_id, id)` slot that `backfillFromChild()`
 * moved out in `up()`, so the two halves round-trip rather than approximately
 * agreeing.
 *
 * Everything else about the rollback is as it was: the restored constraint is
 * the mis-parented one this migration exists to remove, and the docblock at the
 * restore site says so where a reader of `down()` will meet it.
 */
return new class extends Migration
{
    /** The fact row the value belongs on, and the catalog's declared parent. `BotInfo` -> this table. */
    private const PARENT = 'tf_bot_infos';

    /**
     * The fabricated table, and the column the TL names on the parent.
     *
     * TL, verbatim — `schema/sources/TL_telegram_v227.tl:697`:
     *
     * ```
     * botInfo#4d8a0299 flags:# has_preview_medias:flags.6?true user_id:flags.0?long
     *   description:flags.1?string description_photo:flags.4?Photo
     *   description_document:flags.5?Document commands:flags.2?Vector<BotCommand>
     *   menu_button:flags.3?BotMenuButton privacy_policy_url:flags.7?string
     *   app_settings:flags.8?BotAppSettings
     *   verifier_settings:flags.9?BotVerifierSettings = BotInfo;
     * ```
     *
     * `description` parses as `kind=scalar base=string cond=["flags",1]` — a single
     * value, not an object, which is the whole reason the table holding it is
     * fabricated.
     *
     * The name is spelled out at the `Schema::dropIfExists()` call rather than
     * resolved from this constant, because the count reconciliations match the
     * LITERAL. See the docblock's "WHY THE DROP IS A LITERAL AND NOT A LOOP".
     */
    private const CHILD = 'tf_bot_infos_description';

    /**
     * `CHILD` under its other name: the table both children currently, wrongly,
     * point at. It is the same string — `CHILD` names what this migration
     * retires, `OLD_PARENT` names what it is called when something still
     * references it. Kept as a second constant so `declaredConstraint()` can
     * match a constraint TARGET without reading like it re-introduces the table.
     */
    private const OLD_PARENT = 'tf_bot_infos_description';

    /** The column `description` folds onto. */
    private const FOLDED = 'description';

    /**
     * The key every table in this family carries — and it is ALSO the parent's
     * own PRIMARY KEY.
     *
     * Restated rather than derived because `down()` has to reproduce the key
     * EXACTLY, and because the backfill's join depends on it being byte-equal on
     * both tables: a key that is not the same triple on both sides is not a
     * fold, it is a guess.
     *
     * @var list<string>
     */
    private const KEY = ['account_id', 'id'];

    /**
     * The two tables the fabricated table is holding, which this migration
     * returns to `tf_bot_infos`.
     *
     * These are NOT folded and NOT dropped: `description_photo:flags.4?Photo` and
     * `description_document:flags.5?Document` are both `kind=ref`, both are real
     * unions, and the catalog declares both as children of `tf_bot_infos`. Only
     * their PARENT is wrong, and a wrong parent is a constraint, not a table.
     *
     * Looping here is correct — the re-parenting is symmetric across the two, and
     * unlike the DROP nothing scans this file's text for these names.
     *
     * @var list<string>
     */
    private const REPARENTED = [
        'tf_bot_infos_description_photo',
        'tf_bot_infos_description_document',
    ];

    /**
     * The suffix `2026_10_04_000850` gave this family's constraints.
     *
     * Reused rather than invented, so the re-parenting replaces the constraint
     * under its OWN name and `down()` restores the name the shipped dial used.
     * A different suffix here would leave two constraints on each child with the
     * old one still pointing at a table this migration drops.
     */
    private const CONSTRAINT_SUFFIX = '_bot_parent_fk';

    /** Rows per chunk in the backfill. Small because these are facts, not a log. */
    private const CHUNK = 500;

    /** How many rows the backfill carried across. Recorded so a reader sees it ran. */
    private int $moved = 0;

    /** How many constraints were re-pointed. Recorded for the same reason. */
    private int $reparented = 0;

    public function up(): void
    {
        // A dial that never ran has no bot facts and nothing to fold.
        if (! Schema::hasTable(self::PARENT)) {
            return;
        }

        $this->addFoldedColumn();
        $this->backfillFromChild();

        // BEFORE the drop, and this ordering is load-bearing: the current
        // constraints are `cascadeOnDelete()`, so dropping the fabricated table
        // while it is still the parent would delete both children's rows along
        // with it. MySQL would report success.
        $this->reparentChildren();

        // Dropping a table takes everything attached to it. Idempotent: a fresh
        // install, or a re-run, has no table to drop.
        //
        // 🔴 A LITERAL STRING, on purpose — never a class constant, and never a loop.
        // `CuratedDialMigratesOnMysqlTest::tenantFksRetiredAfterThePin()` and the SQLite
        // twin `TenantAccountForeignKeyTest::tenantTablesRetiredAfterThePin()` both count
        // retirements by SCANNING THIS FILE'S TEXT with two regexes: one for a literal
        // table name, one for a `self::` constant that resolves to one. A loop matches
        // neither, so both floors would silently stop counting this retirement and the
        // failure would land in a test this file does not own. This repo has been burned
        // by that twice.
        //
        // 🔴 AND THE SCANNER READS COMMENTS TOO — so this paragraph once cost a
        // one-off failure of its own. It originally spelled out the constant form in
        // backticks to warn against it; `tenantTablesRetiredAfterThePin()` has no
        // comment stripping, matched that warning, resolved the constant, and counted
        // `tf_bot_infos_description` as retired TWICE — 264 − 9 = 255 against a
        // discovered 256, in a test this file also does not own. The lesson is the
        // same one as the loop, and it is why the forbidden spelling is DESCRIBED
        // here rather than QUOTED: on this file, prose about a pattern is the pattern.
        Schema::dropIfExists('tf_bot_infos_description');
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::PARENT)) {
            return;
        }

        // The table comes back FIRST, and its ROWS come back with it, because the
        // children's constraints are pointed at it below and MySQL error 1452
        // refuses a constraint whose child rows have no parent row to point at.
        // The children never moved — `up()` moved the CONSTRAINT, not them — so
        // they are still there, waiting.
        if ($this->restoreChildTable()) {
            // Only when the table was genuinely re-created. On a re-run of
            // down() it already exists WITH its rows, and re-inserting them
            // would be a duplicate-key error rather than a no-op.
            $this->restoreChildRows();
            $this->reparentChildren(self::CHILD);
        }

        if (Schema::hasColumn(self::PARENT, self::FOLDED)) {
            Schema::table(self::PARENT, function (Blueprint $table): void {
                $table->dropColumn(self::FOLDED);
            });
        }
    }

    /**
     * The column, added once, `TEXT NOT NULL DEFAULT ''`.
     *
     * `Schema::hasColumn` rather than a try/catch, so a re-run is a no-op
     * instead of a duplicate-column error — the same idempotence
     * `2026_10_08_000100` and `2026_10_10_000100` rely on.
     *
     * `DB::raw("('')")` rather than `''`: MySQL rejects a literal default on a
     * TEXT column before 8.0.13, and the expression form is what the house
     * `tf_bot_apps` columns already carry. Laravel renders `text()` NOT NULL, so
     * this is the catalog's `"TEXT NOT NULL"` with a default.
     */
    private function addFoldedColumn(): void
    {
        if (Schema::hasColumn(self::PARENT, self::FOLDED)) {
            return;
        }

        Schema::table(self::PARENT, function (Blueprint $table): void {
            $table->text(self::FOLDED)->default(DB::raw("('')"));
        });
    }

    /**
     * Carry every stored description across, before its table goes.
     *
     * PHP, not a correlated UPDATE: it cannot be dialect-wrong, and it lets the
     * value be cast on the way rather than trusting each engine's coercion. The
     * join is the child's own PRIMARY KEY against the parent's, so the two rows
     * are the same botInfo by construction.
     *
     * A NULL in the child would become `''`, because the folded column is NOT NULL
     * and writing the null through would be refused by the engine. That branch
     * is DEFENCE IN DEPTH, not live: MEASURED on the shipped dial, the child's
     * own `description` is already `TEXT NOT NULL` (`2026_09_14_200041` created
     * `$table->text('description')`, which Laravel renders NOT NULL), so MySQL
     * refuses the NULL insert with error 1048 before it can reach here.
     *
     * The skip-if-parent-gone branch below is likewise unreachable on the
     * shipped dial, and for the same reason: the child carries
     * `{child}_bot_parent_fk … ON DELETE CASCADE` and MySQL enforces it, so an
     * orphan child row cannot be inserted at all — error 1452. Both guards stay
     * because an install whose FK is missing, or a driver that does not enforce,
     * can still produce those rows, and a migration that guesses when it could
     * check is worse than one that checks.
     */
    private function backfillFromChild(): void
    {
        $this->moved = 0;

        if (! Schema::hasTable(self::CHILD)) {
            return;
        }

        DB::table(self::CHILD)
            ->select([...self::KEY, self::FOLDED])
            ->orderBy('account_id')
            ->orderBy('id')
            ->chunk(self::CHUNK, function ($rows): void {
                foreach ($rows as $row) {
                    $where = static function ($query) use ($row): void {
                        foreach (self::KEY as $key) {
                            $query->where($key, $row->{$key});
                        }
                    };

                    // A child row whose parent is gone is an orphan this migration
                    // must not paper over by inventing a parent. It is skipped, not
                    // silently dropped into somebody else's row.
                    if (DB::table(self::PARENT)->where($where)->first() === null) {
                        continue;
                    }

                    $value = $row->{self::FOLDED};

                    DB::table(self::PARENT)->where($where)->update([
                        self::FOLDED => $value === null ? '' : $value,
                    ]);

                    $this->moved++;
                }
            });
    }

    /**
     * Point both children's constraint at the real parent.
     *
     * Idempotent from the executed schema rather than from a list: a child that
     * already references `$parent` is left alone, so a re-run is a no-op and an
     * install that has already been migrated keeps its constraint.
     *
     * The drop is by NAME, which every one of the three engines accepts, and the
     * re-add goes back through the grammar under the SAME name — so `down()`
     * finds it, and so a child never ends up carrying two constraints where the
     * old one still names a table this migration retires.
     *
     * @param  string  $parent  `tf_bot_infos` in `up()`; the fabricated table in `down()`.
     */
    private function reparentChildren(string $parent = self::PARENT): void
    {
        if (! Schema::hasTable($parent)) {
            return;
        }

        foreach (self::REPARENTED as $child) {
            if (! Schema::hasTable($child)) {
                continue;
            }

            $constraint = $this->declaredConstraint($child);

            if ($constraint === null) {
                continue;
            }

            // Already correct: leave it, so this is a no-op on a re-run.
            if ((string) ($constraint['foreign_table'] ?? '') === $parent) {
                continue;
            }

            $this->dropForeignOnThisDriver($child, $constraint);

            // 🔴 IN `down()` THIS RESTORES THE MIS-PARENTED CONSTRAINT THIS
            // MIGRATION EXISTS TO REMOVE — and nothing else in this family reads
            // this way. In `up()` the same call is the FIX: it moves
            // `tf_bot_infos_description_{photo,document}_bot_parent_fk` from
            // `tf_bot_infos_description` to `tf_bot_infos`, which is what
            // `mirror-catalog.json` declares. In `down()` it moves them BACK to
            // the fabricated table, because a rollback must return the schema
            // this migration found — and the schema it found has both children
            // hanging off a table that exists only to hold a `flags.1?string`
            // scalar. A reader arriving here from `down()` is looking at a
            // KNOWN DEFECT, restored on purpose. That is not an oversight in the
            // rollback; it is the definition of a rollback.
            $this->linkChild($child, $parent);

            $this->reparented++;
        }
    }

    /**
     * The one link shape in this family: `(account_id, id)` on both sides,
     * `ON DELETE CASCADE`, under `2026_10_04_000850`'s own name.
     *
     * `ON DELETE CASCADE` is kept rather than re-chosen: a botInfo's decomposed
     * unions have no life outside it, which is the family-wide rule that
     * migration records, and a re-parenting that also changed the delete
     * behaviour would be a second change hiding inside a first.
     */
    private function linkChild(string $child, string $parent): void
    {
        $name = $child.self::CONSTRAINT_SUFFIX;

        Schema::table($child, function (Blueprint $table) use ($name, $parent): void {
            $table->foreign(self::KEY, $name)
                ->references(self::KEY)
                ->on($parent)
                ->cascadeOnDelete();
        });
    }

    /**
     * The constraint this family owns on `$child`, read off the engine.
     *
     * Matched by SUFFIX rather than by parent, because the whole point of this
     * migration is that the parent is about to change: matching on the parent
     * would find nothing on the way in and could not tell the family's
     * constraint from `2026_10_04_000700`'s `_acc_tenant_fk` on the way back.
     *
     * @return array<string, mixed>|null
     */
    private function declaredConstraint(string $child): ?array
    {
        $builder = Schema::getConnection()->getSchemaBuilder();

        foreach ($builder->getForeignKeys($child) as $foreign) {
            // 🔴 MATCHED BY ITS TARGET, NEVER BY ITS NAME. MEASURED: SQLite's
            // `PRAGMA foreign_key_list` reports NO constraint name at all —
            // `Schema::getForeignKeys()` returns `'name' => NULL` for every one of
            // these FKs on that engine. A name-based match therefore finds
            // nothing on SQLite, and the previous shape of this method SKIPPED
            // the re-parenting there and then dropped the parent anyway, leaving
            // both children's CREATE TABLE still naming a table that no longer
            // existed — a migration that reported success while doing half the
            // work on one of the three engines this package supports.
            if ((string) ($foreign['foreign_table'] ?? '') !== self::OLD_PARENT
                && (string) ($foreign['foreign_table'] ?? '') !== self::PARENT) {
                continue;
            }

            return $foreign;
        }

        return null;
    }

    /**
     * Drop one child's foreign key, on whichever of the three engines is running.
     *
     * 🔴 D-032, AND THE `else` IS NOT "SOMEWHERE ELSE". Every driver this package
     * supports is named, and an unrecognised one THROWS rather than falling
     * through: a silent fallback is how the constraint this migration exists to
     * remove survives onto an engine nobody tested.
     *
     *   - `mysql` / `pgsql` — drop by NAME. Both record constraint names, and both
     *     need the real name: MySQL compiles `ALTER TABLE … DROP FOREIGN KEY <name>`,
     *     so passing a column array would make it look for a name this dial never
     *     used.
     *   - `sqlite` — drop by COLUMN ARRAY. MEASURED on SQLite:
     *     `$table->dropForeign(['account_id','id'])` removes the clause and
     *     `$table->foreign(['account_id','id'], $name)` re-adds it against the new
     *     parent, which is the only spelling that works when the engine never told
     *     us what the constraint was called.
     */
    private function dropForeignOnThisDriver(string $child, array $foreign): void
    {
        $columns = array_values(array_map('strval', (array) ($foreign['columns'] ?? self::KEY)));
        $name = (string) ($foreign['name'] ?? '');

        match (Schema::getConnection()->getDriverName()) {
            'mysql', 'pgsql' => Schema::table($child, function (Blueprint $table) use ($name): void {
                $table->dropForeign($name);
            }),
            'sqlite' => Schema::table($child, function (Blueprint $table) use ($columns): void {
                $table->dropForeign($columns);
            }),
            default => throw new RuntimeException(sprintf(
                'unrecognised driver: this migration cannot drop the mis-parented constraint on `%s` because '
                .'it does not know how that engine spells `DROP FOREIGN KEY`. Refusing rather than leaving a '
                .'constraint that names a table this migration drops.',
                Schema::getConnection()->getDriverName(),
            )),
        };
    }

    /**
     * Put the fabricated table back, with 200041's own columns, key and types.
     *
     * `text('description')` with no default, because that is what
     * `2026_09_14_200041_create_tf_bots_tables.php` created — a rollback that
     * "improved" the declaration would return a different schema than the one
     * this migration found, and a different schema is not a rollback.
     *
     * No foreign key is declared here: the original table carried none either.
     * `2026_10_04_000850` added the mis-parented constraints to the CHILDREN
     * afterwards, and `reparentChildren()` restores those — which is where the
     * constraint that this migration exists to remove comes back.
     *
     * @return bool TRUE when the table was created by THIS call, FALSE when it
     *              was already there (a re-run of `down()`).
     */
    private function restoreChildTable(): bool
    {
        if (Schema::hasTable(self::CHILD)) {
            return false; // already restored — a re-run of down() is a no-op
        }

        Schema::create(self::CHILD, function (Blueprint $table): void {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->text(self::FOLDED);
            $table->primary(self::KEY);
        });

        return true;
    }

    /**
     * Put the values back, so the re-parenting below has parents to point at.
     *
     * The exact inverse of `backfillFromChild()`: the same `(account_id, id)`
     * slot on both tables, so a value that went out on row 100 comes back on row
     * 100 and the two halves round-trip rather than approximately agreeing.
     *
     * This is REQUIRED, not decorative. The child tables were never emptied —
     * `up()` moved their CONSTRAINT, not their rows — so re-adding a constraint
     * that names a table with no rows is refused by MySQL with error 1452, and
     * the rollback stops half-done. Measured, not reasoned: see the class
     * docblock's "THE ROWS ARE NOT OPTIONAL".
     *
     * PHP through the query builder again, in batches, for the same D-032 reason
     * the forward pass is: a PHP loop cannot be dialect-wrong.
     */
    private function restoreChildRows(): void
    {
        if (Schema::hasColumn(self::PARENT, self::FOLDED) === false) {
            return; // nothing was folded, so there is nothing to give back
        }

        DB::table(self::PARENT)
            ->select(self::KEY)
            ->addSelect(self::FOLDED)
            ->orderBy('account_id')
            ->orderBy('id')
            ->chunk(self::CHUNK, function ($rows): void {
                $batch = [];

                foreach ($rows as $row) {
                    $batch[] = [
                        'account_id' => $row->{self::KEY[0]},
                        'id' => $row->{self::KEY[1]},
                        self::FOLDED => $row->{self::FOLDED},
                    ];
                }

                if ($batch !== []) {
                    DB::table(self::CHILD)->insert($batch);
                }
            });
    }
};
