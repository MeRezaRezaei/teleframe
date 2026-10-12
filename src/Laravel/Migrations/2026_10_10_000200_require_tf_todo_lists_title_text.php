<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `tf_todo_lists_title.text` becomes NOT NULL. The wire says the value is ALWAYS
 * present, so a nullable column admits a row state Telegram has no encoding for.
 *
 * ## THE TL, QUOTED
 *
 * The field reaches this column by two hops, and BOTH are required fields —
 * neither carries a `?` conditional marker, which is what makes a value always
 * present on the wire:
 *
 *   schema/sources/TL_telegram_v227.tl:2067
 *     todoList#49b92a26 flags:# others_can_append:flags.0?true
 *       others_can_complete:flags.1?true title:TextWithEntities
 *       list:Vector<TodoItem> = TodoList;
 *
 *   schema/sources/TL_telegram_v227.tl:1668
 *     textWithEntities#751f3146 text:string entities:Vector<MessageEntity>
 *       = TextWithEntities;
 *
 * `title:TextWithEntities` has no `?`, and inside it `text:string` has no `?`.
 * A TL `string` is a UTF-8 text string that is ALWAYS transmitted; the only
 * absent thing in this vocabulary is the conditional marker, and neither hop
 * carries one. `bin/audit-fidelity.php` reports exactly this as rule C4:
 *
 *   C4  tf_todo_lists_title  column:text
 *       TL text:string — a wire field with no conditional marker is ALWAYS present on the wire
 *       ours: the column is NULLABLE, so a row can carry a value Telegram has no encoding for
 *
 * and it reports it on `--engine=mysql` only: the same run on `--engine=sqlite`
 * prints `COPYING-GAP=0` and names no `tf_todo_lists*` table anywhere in its
 * output (MEASURED 2026-10-10, both runs on this tree, `comparisons=51` on
 * sqlite against `comparisons=53` on MySQL — the whole difference being these
 * two findings). SQLite's nullability IS independently observable, because the
 * shipped dial really does declare this column nullable there:
 *
 *     PRAGMA table_info(tf_todo_lists_title) -> text notnull=0   (measured)
 *
 * so this is a green run describing a state the SQLite schema does not hold,
 * which is the trap this whole exercise is about. The proof below therefore
 * lives on MySQL. I did not establish WHY the sqlite run does not reach the
 * column, and this file does not guess at it.
 *
 * ## WHY NO DEFAULT IS ADDED, WHICH IS A FACT AND NOT AN OMISSION
 *
 * A `string` on the wire has no default. The empty string is a VALUE Telegram
 * sends (`text:""` with an empty `entities` vector is an ordinary empty
 * title), not a fallback the transport substitutes for a missing field — so
 * writing `DEFAULT ''` would invent a value the source never produces, which is
 * the same infidelity in a quieter form.
 *
 * It is also physically impossible on the target engine, and that is worth
 * recording rather than discovering: MySQL/InnoDB refuses a default on a TEXT
 * column with
 *
 *     ERROR 1101 (HY000): BLOB/TEXT column 'text' can't have a default value
 *
 * so a default here would not be a judgement call at all — it would be a
 * dialect failure on MySQL only, which is precisely the "per-driver schema"
 * D-020 exists to prevent.
 *
 * ## WHY A MISSING FIELD NOW COSTS ONE ROW AND NOT AN INGEST OUTAGE
 *
 * This is measured in the writer, not assumed here, because a NOT NULL column is
 * only safe if the seam above it can absorb the refusal.
 *
 * `MirrorFactDecomposer::decompose()` reads a plain scalar with
 *
 *     src/Schema/Mirror/MirrorFactDecomposer.php:208-211
 *         $value = $payload[$field] ?? null;
 *         if ($value !== null) { $row[$column->name] = ...; }
 *
 * so a payload MISSING the field does not write a NULL — it omits the key from
 * the row, and the insert then leaves the column at its default. Before this
 * migration that default was NULL, which is the infidelity. After it, the
 * insert is refused, and `MirrorFactWriter::write()` already owns that exact
 * refusal — by TWO arms, and WHICH one matters, because it is measured rather
 * than assumed.
 *
 * MEASURED on MySQL 8.4.11 in strict mode, 2026-10-10:
 *
 *   - the field ABSENT from the payload  -> errno **1364**
 *     ("Field 'text' doesn't have a default value"). NOT 1048. 1364 is what
 *     an OMITTED NOT NULL column with no default raises; 1048 is what an
 *     explicit `NULL` raises, and the decomposer never writes one.
 *     1364 is a member of
 *         src/Schema/Mirror/MirrorFactWriter.php:351
 *             VALUE_NOT_STORABLE_MYSQL_CODES = [1265, 1364, 1366, 1406]
 *     so it is caught by `isValueNotStorable()` and recorded as a clue whose
 *     own wording already names the fault — "data truncation, encoding or
 *     MISSING-DEFAULT fault" (`MirrorFactWriter.php:749`). The seam
 *     anticipated this case before this migration existed.
 *
 *   - an explicit `NULL`                -> errno **1048**, a member of
 *         src/Schema/Mirror/MirrorFactWriter.php:1567
 *             NOT_NULL_MYSQL_CODES = [1048]
 *     caught by `isNotNullViolation()` and recorded as "NOT NULL constraint
 *     failed (missing required fact data)".
 *
 * Either way the row is skipped and RECORDED as a clue, the batch continues,
 * and no exception escapes `write()`. So the change converts a silently-stored
 * impossible row into a visible, countable one, which is the whole point of the
 * rule, and it does so through arms that were already written and already
 * proven.
 *
 * **THE HONEST LIMIT OF THAT GUARANTEE, stated because a reader will otherwise
 * over-read it:** the refusal is a property of STRICT mode. On a NON-strict
 * server MySQL substitutes `''` and stores the impossible row anyway. Laravel's
 * MySQL connector defaults `strict => true` and the shipped track runs with it
 * (`tests/MySql/MySqlTestCase.php:156`), so this holds on the configured
 * engine — but it is the strict-mode setting doing the work, not the NOT NULL
 * constraint alone, and no test here claims otherwise.
 *
 * ## WHY THE PRE-FLIGHT THROWS INSTEAD OF BACKFILLING `''`
 *
 * An existing NULL here is a row whose `text` Telegram never sent. Backfilling
 * `''` would assert "this title was empty", which the wire does not say, and on
 * a non-strict server the `ALTER` would do exactly that silently. So the count
 * is taken first and a non-zero count aborts with the number and the remedy,
 * leaving the data untouched. This is the same shape as the pre-flight in
 * `2026_10_08_000200_widen_bytes_columns_to_unbounded_text.php`, and for the
 * same reason: the outcome must be identical on a strict and a non-strict
 * server, and the only way to make it so is to refuse before the ALTER.
 *
 * The query is `whereNull(...)->count()`, which is portable as written — no
 * cast, no engine-specific function, nothing to branch on.
 *
 * ## ONE DECLARATION, THREE ENGINES, AND WHY THERE IS NO DRIVER BRANCH
 *
 * `Blueprint::text($column)->nullable(false)->change()` re-declares the WHOLE
 * column from this Blueprint, so the nullability is stated deliberately here
 * rather than inferred. The statement compiles to:
 *
 *   - MySQL 8.4  `ALTER TABLE tf_todo_lists_title MODIFY text TEXT NOT NULL`
 *   - Postgres   `ALTER TABLE ... ALTER COLUMN text TYPE text, ... SET NOT NULL`
 *   - SQLite     Laravel 13 rebuilds the table and records `notnull => 1`
 *
 * There is deliberately NO `match ($driver)` here, and the reason is that this
 * migration has no dialect-dependent decision to make. The three-way branch in
 * D-032 exists because some migrations DO (SQLite accepts a leg MySQL refuses
 * with 3780), and in those the unspecified arm silently becomes MySQL. This one
 * is proven to take the identical path on all three instead — see the proof
 * class `tests/Schema/Mirror/TfTodoListsTitleTextIsNotNullOnMysqlTest` for the
 * MySQL half and `tests/Schema/Mirror/TfTodoListsTitleTextRequiredTest` for the
 * SQLite/Postgres half, and note that `bin/generate-mirror-shape.php` boots the
 * dial on BOTH engines and diffs their constraint sets, so an FK silently lost
 * by a SQLite table rebuild fails that gate rather than passing quietly.
 *
 * `text` is in NO index and is NOT part of the primary key (measured on MySQL
 * 8.4.11 through `information_schema`: `tf_todo_lists_title`'s only index is
 * `PRIMARY` on `(account_id, todo_list_id)`, and `text` carries no
 * `COLUMN_KEY`), so nothing has to be dropped and rebuilt to change it.
 */
return new class extends Migration
{
    /** The table this migration owns. */
    private const TABLE = 'tf_todo_lists_title';

    /** The column the TL declares as always-present `text:string`. */
    private const COLUMN = 'text';

    public function up(): void
    {
        if (! $this->columnIsPresent()) {
            return;
        }

        $this->refuseOverRowsTheWireCannotProduce('up()');

        Schema::table(self::TABLE, function (Blueprint $blueprint): void {
            $blueprint->text(self::COLUMN)
                ->nullable(false)
                ->change();
        });
    }

    public function down(): void
    {
        if (! $this->columnIsPresent()) {
            return;
        }

        // The reverse is unconditionally safe and needs no pre-flight: relaxing a
        // constraint cannot reject, truncate or reinterpret a value. It restores
        // exactly the spelling `2026_10_04_000500_add_text_column_to_tf_todo_lists_title.php`
        // shipped — `$table->text($column)->nullable()` — so a rollback lands the
        // column where that migration left it, with no default and no data change.
        Schema::table(self::TABLE, function (Blueprint $blueprint): void {
            $blueprint->text(self::COLUMN)
                ->nullable(true)
                ->change();
        });
    }

    /**
     * Both guards in one place, so `up()` and any future caller cannot disagree.
     */
    private function columnIsPresent(): bool
    {
        return Schema::hasTable(self::TABLE) && Schema::hasColumn(self::TABLE, self::COLUMN);
    }

    /**
     * Abort loudly when a row exists whose `text` is NULL.
     *
     * @throws RuntimeException
     */
    private function refuseOverRowsTheWireCannotProduce(string $caller): void
    {
        $nulls = Schema::getConnection()
            ->table(self::TABLE)
            ->whereNull(self::COLUMN)
            ->count();

        if ($nulls === 0) {
            return;
        }

        throw new RuntimeException(sprintf(
            '%s refuses to make %s.%s NOT NULL: %d row(s) carry NULL there, and the TL '
            .'(TL_telegram_v227.tl:2067 todoList.title:TextWithEntities -> :1668 textWithEntities.text:string) '
            .'declares the field ALWAYS present, so a NULL is a value Telegram never sent. Backfilling '
            .'`` would assert the title was empty, which the wire does not say. Inspect and clear or correct '
            .'those %d row(s) first -- on a non-strict server the ALTER would otherwise rewrite them silently.',
            $caller,
            self::TABLE,
            self::COLUMN,
            $nulls,
            $nulls,
        ));
    }
};
