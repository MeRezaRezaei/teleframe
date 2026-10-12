<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * `availability_issued`, `host_id_type` and `host_id_id` on BOTH star-gift
 * tables — six columns `MirrorTableResolver` emits and the shipped DDL never
 * declared.
 *
 * ## WHY ONE FILE FOR TWO TABLES: THEY ARE ONE COLUMN SET
 *
 * `tf_stars_transactions_stargift` and `tf_saved_star_gifts_gift` are two
 * different facts that flatten the SAME `StarGift` union (`starGift |
 * starGiftUnique`), and the create migration says so by construction: both call
 * the single shared private helper
 *
 *   self::starGiftColumns($table);
 *
 * (`2026_09_14_200040_create_tf_stars_business_tables.php:252` and `:376`;
 * the helper itself is at `:586`). One helper, one column set, one omission —
 * so one migration fixes the omission. Splitting it would mean writing the same
 * three declarations twice and then keeping two copies of the same reasoning in
 * step, which is how the pair drifted out of the resolver's column set in the
 * first place.
 *
 * ## THE OMISSION, MEASURED ITEM BY ITEM
 *
 * `tests/Schema/Mirror/ResolvedColumnsMatchShippedDdlTest::
 *  test_no_write_reachable_table_lacks_a_column_the_resolver_emits()` names all
 * six. The shared helper declares 46 columns; the resolver emits 49. The three
 * it does not declare are exactly:
 *
 *   availability_issued   integer   ->  $table->integer(...)     -> DEFAULT 0
 *   host_id_type          tinyint   ->  $table->tinyInteger(...) -> DEFAULT 0
 *   host_id_id            bigint    ->  $table->bigInteger(...)  -> DEFAULT 0
 *
 * read off a live `MirrorTableResolver::resolveAll()` run over the real scheme
 * for both tables. So this file declares them at exactly those widths.
 *
 * The helper's own neighbours show this is an omission and not a considered
 * deferral. It already declares the structurally IDENTICAL pairs that sit a few
 * lines away in the same constructor:
 *
 *   owner_id_type   / owner_id_id      (TinyInt / BigInt)
 *   theme_peer_type / theme_peer_id    (TinyInt / BigInt)
 *   released_by_type/ released_by_id   (TinyInt / BigInt)
 *
 * `host_id` is the same `Peer`-shaped fact as `owner_id` and `theme_peer`, it
 * simply was not transcribed. A field the helper does declare for a sibling and
 * not for this one is an omission; a field the resolver never emits would not
 * appear in the gate's list at all.
 *
 * ## WHAT IT COSTS WHEN THE COLUMNS ARE MISSING
 *
 * The double silent drop this gate exists to close (see the test's class
 * docblock for the measured instance of this species): `MirrorFactWriter::write()`
 * strips a column the live table does not have, recording an `unmappedClues`
 * entry, and the row is then declined by whichever sibling column is NOT NULL
 * with no default. Nothing raises; the only record is a clue string.
 *
 * ## `DEFAULT 0` IS THE HELPER'S OWN CONVENTION, NOT A NEW ONE
 *
 * Every non-key column `starGiftColumns()` declares carries `->default(0)` —
 * `availability_remains`, `availability_total`, `availability_resale`,
 * `offer_min_stars`, `craft_chance_permille` and the rest. These three are added
 * with the same default so the shipped shape of the two tables stays uniform,
 * and so the ALTER runs against an install that already holds rows (MySQL in
 * strict mode refuses a NOT NULL column added without a default — ERROR 1364).
 *
 * For the two peer halves, 0 is also the correct ABSENT value: `host_id` is
 * optional, and the writer leaves both halves at their default rather than
 * inventing a peer Telegram did not send — the same convention
 * `2026_10_04_000300_add_recent_repliers_to_tf_messages_replies.php` follows
 * for its own pair.
 *
 * ## NO TABLE IS CREATED, NO KEY IS TOUCHED, NO FOREIGN KEY IS DECLARED
 *
 * This is column-only and additive. Both keys stay exactly as shipped —
 * `primary(['account_id', 'id'])` on each — because widening or narrowing a key
 * is a separate decision with its own blast radius (pinned independently by
 * `MirrorResolverKeyMatchesShippedDdlTest`).
 *
 * None of the four MySQL portability anti-patterns apply, and all four are
 * enforced over every file in this directory by `MySqlDialPortabilityTest`:
 * no `->foreign()` is declared, so no constraint name exists to exceed the
 * 64-character ceiling (MySQL-1059); no TEXT column is added, so no bare
 * default can arise (MySQL-1101); nothing is indexed, so no TEXT column can
 * enter an index key (MySQL-1170); and `account_id` is never declared here, so
 * the signed/unsigned question (MySQL-3780) does not arise.
 *
 * ## ADDITIVE, PER THIS REPO'S CONVENTION
 *
 * The alternative — adding the three declarations to `starGiftColumns()` in
 * place — is forbidden and would be a defect, not a shortcut: an install that
 * already ran `2026_09_14_200040` has it recorded as applied, so editing it
 * would leave the gap in place forever. Same reasoning and same convention as
 * `2026_10_04_000100_make_user_id_unsigned_unique.php`,
 * `2026_10_04_000200_retarget_tenant_foreign_keys.php` and
 * `2026_10_04_000300_add_recent_repliers_to_tf_messages_replies.php`.
 */
return new class extends Migration
{
    /**
     * The two tables, and the ONLY set `down()` reverses.
     *
     * @var list<string>
     */
    private const TABLES = [
        'tf_stars_transactions_stargift',
        'tf_saved_star_gifts_gift',
    ];

    /**
     * The columns added to EACH table above, and the only set `down()` drops.
     *
     * `addColumn()` throws on any name it does not handle, so a column listed
     * here without a declared width is a loud failure rather than a silently
     * mis-typed column.
     *
     * @var list<string>
     */
    private const COLUMNS = [
        'availability_issued',
        'host_id_type',
        'host_id_id',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            $this->eachMissingColumn($table, function (string $name) use ($table): void {
                Schema::table($table, function (Blueprint $blueprint) use ($name): void {
                    $this->addColumn($blueprint, $name);
                });
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            $this->eachMissingColumn($table, function (string $name) use ($table): void {
                Schema::table($table, function (Blueprint $blueprint) use ($name): void {
                    $blueprint->dropColumn($name);
                });
            });
        }
    }

    /**
     * Declare one owned column at the width the resolver emits.
     *
     * `DEFAULT 0` matches every other column `starGiftColumns()` declares — see
     * the file docblock on why the default is required and what 0 means.
     */
    private function addColumn(Blueprint $table, string $column): void
    {
        match ($column) {
            'availability_issued' => $table->integer($column)->default(0),
            'host_id_type' => $table->tinyInteger($column)->default(0),
            'host_id_id' => $table->bigInteger($column)->default(0),
            default => throw new RuntimeException(sprintf(
                'no column type is declared for %s, so this file cannot add it. Every name in COLUMNS must have '
                .'a case in addColumn() — an unhandled one is a defect, not a column to skip.',
                $column,
            )),
        };
    }

    /**
     * Run `$work` once per owned column that `$table` does not already carry.
     *
     * Guarded on `hasTable`/`hasColumn` in both directions, this family's
     * established style (2026_09_14_000000, 2026_09_24_000101, 2026_09_26_000100,
     * 2026_09_27_000100): a re-run of `up()`, or a second `down()`, is a no-op
     * instead of a duplicate-column or missing-column error. The `hasTable`
     * guard is per table rather than once for the pair, because the two tables
     * are created by the same helper and an install can legitimately be missing
     * one of them.
     *
     * @param  callable(string): void  $work  receives the column name
     */
    private function eachMissingColumn(string $table, callable $work): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        foreach (self::COLUMNS as $column) {
            if (Schema::hasColumn($table, $column)) {
                continue;
            }

            $work($column);
        }
    }
};
