<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * `tf_messages_replies.recent_repliers_type` / `.recent_repliers_id` — the two
 * columns `MirrorTableResolver` emits and the shipped DDL never declared.
 *
 * ## THE DEFECT, MEASURED, NOT INFERRED
 *
 * `tests/Schema/Mirror/ResolvedColumnsMatchShippedDdlTest::
 *  test_no_write_reachable_table_lacks_a_column_the_resolver_emits()` names
 * both, and names them because the row is lost TWICE and silently:
 *
 *   1. `MirrorFactWriter::write()` pre-filters every row against the live
 *      table's column listing and STRIPS a column the table does not have,
 *      recording an `unmappedClues` entry — so the value is gone BEFORE the
 *      insert is ever attempted;
 *   2. the row is then declined by whichever sibling column is NOT NULL with
 *      no default.
 *
 * The only record is a clue string. See that test's class docblock for the
 * measured instance of this exact species (`tf_saved_star_gifts_collection_id`,
 * 3 unmapped clues + 3 fkClues + 0 stored rows for a three-element value).
 *
 * ## WHY THE DDL IS THE INCOMPLETE SIDE — THE CONSTRUCTOR CARRIES THE FIELD
 *
 * `messageReplies` is a single-ctor type and its declaration DOES carry the
 * pair, verbatim (`schema/sources/TL_telegram_v227.tl:1418`; the same line in
 * `src/Schema/schema/sources/api.tl:1421` and `api_full.tl:1418`):
 *
 *   messageReplies#83d60fc2 flags:# comments:flags.0?true replies:int
 *     replies_pts:int recent_repliers:flags.1?Vector<Peer>
 *     channel_id:flags.0?long max_id:flags.2?int read_max_id:flags.3?int
 *     = MessageReplies;
 *
 * A field that is absent from the scheme cannot be resolved into a column; this
 * one is present, so its absence from the DDL is a gap and not a deferral.
 * `MirrorFieldDecomposer::fromShape()` (`src/Schema/Mirror/MirrorFieldDecomposer.php:44-47`)
 * is what mints the pair — any peer-shaped field that is not literally named
 * `peer_id` expands to `{field}_type` TINYINT + `{field}_id` BIGINT, both
 * peer-flagged — which is exactly the `recent_repliers_type` / `recent_repliers_id`
 * the resolver hands the writer.
 *
 * An earlier lane in this repo concluded that this constructor was ABSENT from
 * the scheme and that the resolver was therefore inventing the columns. That
 * conclusion is REFUTED, and it is recorded here because a refuted probe is
 * exactly what a later reader will otherwise re-derive: the accessor used was
 * wrong, the field was there the whole time. When in doubt the line above is
 * the arbiter, not a summary of it.
 *
 * ## WHAT THE CREATE MIGRATION CLAIMED, AND WHY IT IS HALF RIGHT
 *
 * `2026_09_14_200010_create_tf_messages_tables.php:404` annotates this table:
 *
 *   // replies — single-ctor messageReplies flattened. recent_repliers
 *   // vector is unkeyable under the flat write path → deferred.
 *
 * "Deferred" is right about the VECTOR and wrong about the TABLE. The 1:N
 * child table `tf_messages_replies_recent_repliers` — one row per element,
 * keyed by `position` — is a separate synthesized child
 * (`MirrorTableResolver::resolveVectorFromType()`, `src/Schema/Mirror/MirrorTableResolver.php:506-524`)
 * and remains deferred, exactly as annotated. What the flat table ALSO carries
 * is the parent-level peer pair for that same field, and those two columns are
 * what the writer looks for on the parent row. Declaring the vector deferred
 * while leaving the parent's own pair undeclared is the gap this file closes.
 * Nothing here re-enables the child table and no new table is created.
 *
 * ## TYPES ARE THE RESOLVER'S, MEASURED
 *
 * MEASURED by running the real resolver over the real scheme and printing the
 * resolved column list for `tf_messages_replies` (the probe is the gate's own
 * code path: `MirrorCatalog::load()` + `MirrorTableResolver::resolveAll()`):
 *
 *   recent_repliers_type   TinyInt   sql=TINYINT
 *   recent_repliers_id     BigInt    sql=BIGINT
 *
 * so this file declares `tinyInteger` and `bigInteger`. `DEFAULT 0` matches the
 * convention the create migration already uses for this table's other optional
 * facts (`channel_id`, `max_id`, `read_max_id` all ship `->default(0)`), and it
 * is what makes the ALTER safe on an install that already holds rows: a NOT
 * NULL column added without a default is refused by MySQL in strict mode
 * (ERROR 1364, `Field '...' doesn't have a default value`).
 *
 * `DEFAULT 0` is also the correct PEER value, not a placeholder: the pair is
 * absent whenever `flags.1` is clear, and the writer leaves both halves at
 * their default rather than inventing a peer that Telegram did not send.
 *
 * ## ADDITIVE, PER THIS REPO'S CONVENTION
 *
 * The alternative — adding the two lines to the create migration in place — is
 * forbidden and would be a defect, not a shortcut: an install that already ran
 * `2026_09_14_200010` has it recorded as applied, so editing it would leave
 * the gap in place forever. The same reasoning and the same convention as
 * `2026_10_04_000100_make_user_id_unsigned_unique.php` and
 * `2026_10_04_000200_retarget_tenant_foreign_keys.php`.
 *
 * NO PRIMARY KEY IS TOUCHED. This is a column-only additive migration: the key
 * stays `primary(['account_id', 'peer_type', 'peer_id', 'id'])`, exactly as
 * shipped, because widening or narrowing a key is a separate decision with its
 * own blast radius (see `MirrorResolverKeyMatchesShippedDdlTest`, which pins
 * the shipped key against the resolver's).
 *
 * ## NO FOREIGN KEY IS DECLARED, AND NONE OF THE FOUR MySQL ANTI-PATTERNS APPLY
 *
 * This file declares no `->foreign()` at all, so no implicit or explicit
 * constraint name exists to exceed the 64-character identifier ceiling
 * (MySQL-1059); it adds no TEXT column, so no bare default can arise
 * (MySQL-1101); it indexes nothing, so no TEXT column can enter an index key
 * (MySQL-1170); and it never touches `account_id`, so the signed/unsigned
 * question (MySQL-3780) does not arise either. Those four rules are enforced
 * over every file in this directory by `MySqlDialPortabilityTest`.
 */
return new class extends Migration
{
    /** The table this migration adds columns to. */
    private const TABLE = 'tf_messages_replies';

    /**
     * The columns this file owns, and the ONLY set `down()` reverses.
     *
     * Kept as one list so the two directions cannot drift: `up()` walks this
     * map, and `addColumn()` — which spells out each type rather than
     * dispatching on a string — throws on any name that is not handled, so a
     * column added here without a type is a loud failure rather than a silently
     * mis-typed column.
     *
     * @var list<string>
     */
    private const COLUMNS = [
        'recent_repliers_type',
        'recent_repliers_id',
    ];

    /** The direction `up()` walks: act on the columns that are ABSENT. */
    private const ADD = 'add';

    /** The direction `down()` walks: act on the columns that are PRESENT. */
    private const DROP = 'drop';

    public function up(): void
    {
        $this->eachOwnedColumn(self::ADD, function (string $column): void {
            Schema::table(self::TABLE, function (Blueprint $table) use ($column): void {
                $this->addColumn($table, $column);
            });
        });
    }

    public function down(): void
    {
        // Reverses up() exactly: the same list, dropped. A down() that dropped
        // fewer columns than up() added, or renamed one on the way, would not
        // be a down().
        $this->eachOwnedColumn(self::DROP, function (string $column): void {
            Schema::table(self::TABLE, function (Blueprint $table) use ($column): void {
                $table->dropColumn($column);
            });
        });
    }

    /**
     * Declare one owned column, at the width the resolver emits.
     *
     * `DEFAULT 0` is the absent peer rather than a placeholder: the field is
     * flags.1?-gated, so both halves stay at their default when Telegram sends
     * no `recent_repliers`. It is also what makes the ALTER safe against an
     * install that already holds rows — MySQL in strict mode refuses a NOT NULL
     * column added without a default (ERROR 1364).
     */
    private function addColumn(Blueprint $table, string $column): void
    {
        match ($column) {
            'recent_repliers_type' => $table->tinyInteger($column)->default(0),
            'recent_repliers_id' => $table->bigInteger($column)->default(0),
            default => throw new RuntimeException(sprintf(
                'no column type is declared for %s.%s, so this file cannot add it. Every name in COLUMNS '
                .'must have a case in addColumn() — an unhandled one is a defect, not a column to skip.',
                self::TABLE,
                $column,
            )),
        };
    }

    /**
     * Run `$work` once per owned column that is not yet in the state `$work`
     * would put it in — where WHICH STATE THAT IS depends on the direction.
     *
     * ## WHY THE DIRECTION IS AN ARGUMENT AND NOT AN INHERITED PREDICATE
     *
     * This helper used to guard on `hasColumn` unconditionally, which is
     * correct for an ADDER and exactly INVERTED for a DROPPER. `down()` routed
     * its dropper through it on a comment saying it "reverses up() exactly", so
     * every column `up()` had just added was found present, skipped, and never
     * dropped: `down()` executed ZERO DDL, returned normally, Laravel deleted
     * the migration row, and the rollback was silently void while every signal
     * reported success. Measured on :memory: SQLite against this file, before
     * the fix — both columns still present after `down()`, `removed: []`.
     *
     * So the direction is a NAMED ARGUMENT here, and the predicate is derived
     * from it. ONE place expresses "which state is the one I act on", which is
     * the whole defence: a second, sibling `eachExistingColumn()` would put the
     * same inverted-predicate shape in two places and hand the next reader the
     * harder job.
     *
     * The `match` is the second half of the defence and is deliberately
     * THROWING. This helper serves four directions (add/drop × up/down), and an
     * unrecognised one must be a loud failure rather than a silently-skipped
     * column — the failure mode being fixed here is precisely a silent one.
     * Same idiom, and the same reasoning, as `addColumn()` above.
     *
     * Both directions stay guarded on `hasTable`/`hasColumn`, which is this
     * family's established style (2026_09_14_000000, 2026_09_24_000101,
     * 2026_09_26_000100, 2026_09_27_000100): a re-run of `up()`, or a second
     * `down()`, is a no-op instead of a duplicate-column or missing-column
     * error. One `Schema::table()` call per column rather than a single call
     * adding all of them, because a Blueprint that re-adds a column another
     * install already has is exactly the case the guard is there to absorb.
     *
     * @param  string  $direction  self::ADD or self::DROP, and nothing else: which
     *                             state to act on, absent or present. Typed `string`
     *                             rather than the `self::ADD|self::DROP` union ON
     *                             PURPOSE — that union would narrow the parameter to
     *                             `'add'|'drop'`, make the `match` provably exhaustive,
     *                             and silence the very `default` arm that turns an
     *                             unrecognised direction into a throw. The runtime
     *                             `match` is the enforcement; this annotation would
     *                             remove the enforcement's reason to exist.
     * @param  callable(string): void  $work  receives the column name
     */
    private function eachOwnedColumn(string $direction, callable $work): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            return;
        }

        // true = act on the PRESENT columns (drop); false = act on the ABSENT
        // ones (add). Anything else is a defect, not a column to skip.
        $actWhenPresent = match ($direction) {
            self::ADD => false,
            self::DROP => true,
            default => throw new RuntimeException(sprintf(
                'unknown direction "%s" passed to eachOwnedColumn() for %s. It must be self::ADD (act on the '
                .'columns the table is missing) or self::DROP (act on the columns it carries) — an unrecognised '
                .'direction is a defect, not a reason to walk no columns and silently reverse nothing.',
                $direction,
                self::TABLE,
            )),
        };

        foreach (self::COLUMNS as $column) {
            if (Schema::hasColumn(self::TABLE, $column) !== $actWhenPresent) {
                continue;
            }

            $work($column);
        }
    }
};
