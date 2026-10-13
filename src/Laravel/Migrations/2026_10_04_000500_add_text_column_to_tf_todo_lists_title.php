<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * `tf_todo_lists_title.text` — the column `MirrorTableResolver` emits, which
 * the shipped DDL spells `title`.
 *
 * ## WHY ADD `text` AND LEAVE `title` ALONE
 *
 * The obvious alternative is to RENAME `title` to `text`, and for a table that
 * no row has ever reached that would be the tidier shape. It is deliberately
 * NOT done here, for one reason that is a constraint rather than a preference:
 *
 * `tests/Schema/Mirror/ResolvedColumnsMatchShippedDdlTest` PINS
 * `tf_todo_lists_title.title` by name, in its `DDL_ONLY_COLUMNS` constant, as a
 * column the DDL declares and the resolver never emits — one of a measured 27.
 * Renaming the column would make that pinned entry DISAPPEAR, and the gate
 * asserts the set by exact equality, so the rename turns the mirror image of
 * this gate red. Fixing that constant is not this file's to do: the gate is the
 * specification for this migration, and a migration that edits its own
 * specification proves nothing.
 *
 * So `text` is added and `title` stays. `title` is inert either way — it is a
 * pinned DDL-only column, meaning no write path produces it — so keeping it
 * costs one nullable-ish dead column per row and buys an additive migration
 * that no install's existing data is at risk from. A rename is a destructive
 * migration on a shipped column; an addition is not. `tf_todo_lists_list` has
 * the same `title`-instead-of-`text` shape and is left untouched for the same
 * reason, and is reported as the sibling instance rather than widened into.
 *
 * ## WHY THE RESOLVER IS RIGHT AND `title` IS THE OUTLIER — MEASURED
 *
 * The catalog entry for the parent declares the field as a
 * `FK→TextWithEntities` object fact:
 *
 *   "tf_todo_lists": { "base": [ ["title","FK→TextWithEntities"], ["list","1:N child"] ] }
 *
 * (measured in `src/Schema/schema/mirror-catalog.json`). A `FK→` fact does not
 * flatten into a column named after the PARENT's field name — it decomposes to
 * the TARGET type's own single scalar field, which is
 *
 *   textWithEntities#751f3146 text:string entities:Vector<MessageEntity>
 *
 * (`schema/sources/TL_telegram_v227.tl:1668`). So the resolved column is
 * `text`, and it is `tinyint`-free plain `string` -> TEXT.
 *
 * This is not a new observation and the repo has already been bitten by it: the
 * create migration for the SIBLING `tf_todo_items_title` declares `$table->text('text')`
 * and carries a long note recording that its own column was originally declared
 * `title` while the resolver emitted `text`
 * (`2026_09_14_200050_create_tf_misc_tables.php:145-171`). That same comment is
 * the origin of the two facts worth repeating here: the divergence made the
 * value be "unset as unmodelled" AND then failed the empty NOT NULL column, so
 * the row was lost twice; and its author recorded that `tf_todo_lists_title` was
 * knowingly left declaring `title`, on the grounds that
 *
 *   "no row can reach them yet, because their parent's `todo_list_id` key is
 *    synthetic and unfillable, so no behavioural test can be written for them;
 *    they are reported, not guessed at."
 *
 * That reasoning was sound for a BEHAVIOURAL test and wrong for this one. The
 * DDL contract is asserted over the resolver's whole reachable graph, not over
 * the subset a behavioural test can reach — which is the generalisable rule
 * `docs/decisions.md` D-007 records. The columns are added here, and the
 * behavioural gap it names is exactly why no behavioural proof accompanies them.
 *
 * ## `NULLABLE`, AND WHY IT IS NOT `NOT NULL`
 *
 * The sibling `tf_todo_items_title.text` is declared NOT NULL with no default,
 * because it is created inside a `Schema::create` on a table that is empty at
 * that moment. This is an ALTER on a table that may already hold rows, and MySQL
 * in strict mode refuses to add a NOT NULL column without a default (ERROR
 * 1364). `->nullable()` is this family's answer to exactly that, and it is
 * precedented in this directory:
 * `2026_09_24_000101_add_peer_type_to_tf_store_buckets_table.php` adds
 * `->string('peer_type', 16)->nullable()` with a comment saying why.
 *
 * The asymmetry is deliberate and one-directional: nullability can be tightened
 * later by a separate ALTER on an empty table, whereas a NOT NULL column cannot
 * be relaxed for installs that already ran it. So the safe shape ships first.
 * The residual divergence — `tf_todo_lists_title.text` nullable where
 * `tf_todo_items_title.text` is NOT NULL — is reported, not silently resolved
 * by guessing that the table is empty because a 2026-09-14 comment says so.
 *
 * ## NO TEXT DEFAULT, AND NONE OF THE FOUR MySQL ANTI-PATTERNS APPLY
 *
 * The column carries NO default at all, so the MySQL-1101 rule (a TEXT column
 * with a bare default) cannot arise; the create migrations spell their TEXT
 * defaults as `->default(DB::raw("('')"))` precisely because of that rule, and
 * this file needs no such expression. No `->foreign()` is declared, so no
 * constraint name exists to exceed the 64-character ceiling (MySQL-1059);
 * nothing is indexed, so no TEXT column can enter an index key (MySQL-1170) —
 * the table's key stays `primary(['account_id', 'todo_list_id'])`, which is
 * integral columns only; and `account_id` is never declared here, so the
 * signed/unsigned question (MySQL-3780) does not arise. All four are enforced
 * over every file in this directory by `MySqlDialPortabilityTest`.
 *
 * ## ADDITIVE, PER THIS REPO'S CONVENTION
 *
 * Editing `2026_09_14_200050_create_tf_misc_tables.php:190-201` in place would
 * be a defect, not a shortcut: an install that already ran it has it recorded as
 * applied, so the column would stay missing forever. Same reasoning and same
 * convention as `2026_10_04_000100_make_user_id_unsigned_unique.php`,
 * `2026_10_04_000200_retarget_tenant_foreign_keys.php`,
 * `2026_10_04_000300_add_recent_repliers_to_tf_messages_replies.php` and
 * `2026_10_04_000400_add_star_gift_host_and_availability_columns.php`.
 */
return new class extends Migration
{
    /** The table this migration adds a column to. */
    private const TABLE = 'tf_todo_lists_title';

    /**
     * The column added, and the only one `down()` drops.
     *
     * A single-element list rather than a bare string so `up()` and `down()`
     * walk the same declaration and cannot drift.
     *
     * @var list<string>
     */
    private const COLUMNS = [
        'text',
    ];

    /** The direction `up()` walks: act on the column that is ABSENT. */
    private const ADD = 'add';

    /** The direction `down()` walks: act on the column that is PRESENT. */
    private const DROP = 'drop';

    public function up(): void
    {
        $this->eachOwnedColumn(self::ADD, function (string $column): void {
            Schema::table(self::TABLE, function (Blueprint $table) use ($column): void {
                match ($column) {
                    'text' => $table->text($column)->nullable(),
                    default => throw new RuntimeException(sprintf(
                        'no column type is declared for %s.%s, so this file cannot add it. Every name in COLUMNS '
                        .'must have a case here — an unhandled one is a defect, not a column to skip.',
                        self::TABLE,
                        $column,
                    )),
                };
            });
        });
    }

    public function down(): void
    {
        // Reverses up() exactly: the same column, dropped.
        $this->eachOwnedColumn(self::DROP, function (string $column): void {
            Schema::table(self::TABLE, function (Blueprint $table) use ($column): void {
                $table->dropColumn($column);
            });
        });
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
     * the column `up()` had just added was found present, skipped, and never
     * dropped: `down()` executed ZERO DDL, returned normally, Laravel deleted
     * the migration row, and the rollback was silently void while every signal
     * reported success. Measured on :memory: SQLite against this file, before
     * the fix — `text` still present after `down()`, `removed: []`.
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
     * Same idiom, and the same reasoning, as the column-type `match` in
     * `up()` above.
     *
     * Both directions stay guarded on `hasTable`/`hasColumn`, which is this
     * family's established style (2026_09_14_000000, 2026_09_24_000101,
     * 2026_09_26_000100, 2026_09_27_000100): a re-run of `up()`, or a second
     * `down()`, is a no-op instead of a duplicate-column or missing-column
     * error.
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

        // true = act on the PRESENT column (drop); false = act on the ABSENT
        // one (add). Anything else is a defect, not a column to skip.
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
