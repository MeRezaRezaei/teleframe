<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The three curated TL `bytes` columns become UNBOUNDED TEXT. `varchar(2048)` was
 * a hard ceiling of 1024 RAW BYTES, and overflow was a SILENT ROW LOSS.
 *
 * ## THE DEFECT, AND WHY IT NEVER REACHES THE OPERATOR
 *
 * A TL `bytes` value is not stored raw. It is stored as LOWERCASE HEX, produced
 * by the one encode site `MirrorFactDecomposer::encodeBytes()`
 * (`src/Schema/Mirror/MirrorFactDecomposer.php:717`, `bin2hex()`). Hex is exactly
 * two ASCII characters per raw byte, so the three curated declarations
 *
 *     2026_09_14_200020_create_tf_documents_tables.php:68   string('bytes', 2048)
 *     2026_09_14_200021_create_tf_photos_tables.php:36       string('bytes', 2048)
 *     2026_09_14_200023_create_tf_sticker_sets_tables.php:56 string('bytes', 2048)
 *
 * impose a HARD CEILING OF 1024 RAW BYTES on the largest value Telegram can
 * deliver into those columns: `document.file_reference`, `document.waveform`, the
 * `photoSize` / `photoStripSize` / `photoSizeProgressive` `bytes`, and the
 * sticker-set thumb `bytes`.
 *
 * 1024 bytes is not a comfortable bound for any of them. A real
 * `photoSize.bytes` for a large image, and a `document.waveform` for a long voice
 * note, both exceed it routinely.
 *
 * The overflow does not raise anything a user of this package would see. MEASURED
 * on MySQL 8.4.11 this run, against a table carrying the shipped declaration
 * `bytes VARCHAR(2048) NOT NULL DEFAULT ('')` and a `REPEAT('ab', 2048)` value:
 *
 *     mysql> INSERT INTO tf_documents_thumbs VALUES
 *         -> (1, 1, 0, 'x', 'photoSize', 1, 1, 8000, REPEAT('ab', 2048));
 *     ERROR 1406 (22001) at line 13: Data too long for column 'bytes' at row 1
 *
 * `ERROR 1406` and `ERROR 1265` are BOTH members of
 * `MirrorFactWriter::VALUE_NOT_STORABLE_MYSQL_CODES`
 * (`src/Schema/Mirror/MirrorFactWriter.php:349`), whose whole purpose is to
 * classify an insert the engine refused as "this value does not fit this column"
 * and SKIP THE ROW. So the failure mode is:
 *
 *   Telegram carries a 1500-byte `waveform`
 *     -> the mirror composer emits the row
 *       -> MySQL raises 1406
 *         -> MirrorFactWriter classifies it as not-storable and SKIPS it
 *           -> NO exception, NO log entry, NO alarm
 *             -> the mirror now silently disagrees with Telegram
 *
 * That is data loss disguised as a schema constraint. It is precisely the defect
 * class this repository's MySQL track exists to catch, and it is why
 * `tests/MySql/TfBytesColumnsAreUnboundedOnMysqlTest.php` extends `MySqlTestCase`
 * rather than the default track: SQLite DOES NOT ENFORCE `VARCHAR(n)` AT ALL, so a
 * sqlite run of that file is vacuously green by construction. **This migration's
 * claim is therefore PROVEN ON MySQL, and sqlite alone proves nothing about it.**
 * The sqlite role here is only that it must not BREAK — see "CROSS-ENGINE".
 *
 * ## WHY TEXT, AND WHY `text` AND NOT `mediumtext` / `longtext`
 *
 * `text` is MySQL's smallest TEXT-family type and its floor is 65535 CHARACTERS
 * (`CHARACTER_MAXIMUM_LENGTH` measured as `65535` below). Because the stored form
 * is hex — pure ASCII, two characters per byte — 65535 characters is 32767 RAW
 * BYTES, a 32× improvement on the ceiling this migration removes, and it exceeds
 * the largest realistic `file_reference` / `waveform` by a wide margin.
 *
 * `mediumtext` (16777215) and `longtext` (4294967295) were considered and
 * rejected for a reason that is not "TEXT is big enough":
 *
 *   - `longtext` cannot be the target of a full-table `ORDER BY` / index sort at
 *     all on InnoDB, and it is the type most often reached for reflexively in a
 *     "just make it unbounded" patch, which overstates the change.
 *   - `mediumtext` buys nothing here: it moves the ceiling by 256× over a value
 *     that is a document reference or a waveform, not a media payload. Media
 *     payloads travel through `tf_storer*` / the S3 store, not through a hex text
 *     column — which is the entire point of D-034's Decision 1 keeping the
 *     payload out of the relational mirror.
 *
 * `text` is the smallest type that removes the defect, and choosing a larger one
 * would be a claim about capacity that no measured value supports.
 *
 * ## WHY THE DEFAULT IS KEPT, AND WHY IT IS THE `DB::raw` EXPRESSION FORM
 *
 * The shipped columns carry `->default(DB::raw("('')"))`. That default is part of
 * the column's contract — `MirrorFactWriter` supplies `bytes` on every insert, but
 * a NOT NULL column with no default is what makes an omitted key a hard failure,
 * and this family's house style (`tests/Schema/MySqlDialPortabilityTest.php`,
 * rule MySQL-1101) requires any TEXT default to be a `DB::raw` EXPRESSION rather
 * than a bare literal.
 *
 * **A correction to the record, because a docblock that states a falsehood is
 * worse than no docblock.** `TfBytesColumnsAreUnboundedOnMysqlTest`'s class
 * docblock asserts that "MySQL forbids a literal default on a TEXT column
 * outright (error 1101)". That is NOT true of the engine this repository targets.
 * MEASURED on MySQL 8.4.11, this run:
 *
 *     mysql> ALTER TABLE tf_documents_thumbs
 *         -> MODIFY bytes TEXT NOT NULL DEFAULT ((''));
 *     Query OK, 0 rows affected
 *     mysql> SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH,
 *         ->        IS_NULLABLE, COLUMN_DEFAULT, EXTRA
 *         ->   FROM information_schema.COLUMNS
 *         ->   WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tf_documents_thumbs'
 *         ->     AND COLUMN_NAME='bytes'\G
 *                 COLUMN_NAME: bytes
 *                   DATA_TYPE: text
 *         CHARACTER_MAXIMUM_LENGTH: 65535
 *                   IS_NULLABLE: NO
 *                 COLUMN_DEFAULT: _utf8mb4\'\'
 *                        EXTRA: DEFAULT_GENERATED
 *
 * The parenthesised expression form `((''))` — which is exactly what
 * `->default(DB::raw("('')"))` emits — is ACCEPTED, and MySQL records it as
 * `EXTRA: DEFAULT_GENERATED`. MySQL 8.0.13 lifted the BLOB/TEXT-default ban for
 * expression defaults; 8.4.11 is well past that. The 1101 the test docblock
 * describes is the pre-8.0.13 behaviour.
 *
 * So the default is KEPT, not dropped, and both outcomes the test anticipates
 * ("both outcomes are legal") hold — this file picks the one that preserves the
 * column's shipped contract. The `DB::raw` SPELLING is still mandatory, but by
 * this repo's own static rule, not by the engine.
 *
 * ## `NOT NULL` IS PRESERVED, AND MEASURED RATHER THAN ASSUMED
 *
 * A bare `$table->text('bytes')->change()` on MySQL DROPS `NOT NULL` — `change()`
 * re-declares the whole column from the Blueprint you hand it, so anything you
 * omit is dropped. MEASURED, this run, after a plain
 * `ALTER ... MODIFY bytes TEXT`:
 *
 *                 COLUMN_NAME: bytes
 *                   DATA_TYPE: text
 *         CHARACTER_MAXIMUM_LENGTH: 65535
 *                   IS_NULLABLE: YES          <-- NOT NULL silently lost
 *                  COLUMN_DEFAULT: NULL
 *
 * Re-adding it explicitly is legal on the target engine — `MODIFY bytes TEXT NOT
 * NULL` is accepted and reports `IS_NULLABLE: NO` — so the shipped nullability is
 * carried across deliberately rather than by accident.
 *
 * ## MySQL-1170: NO `bytes` COLUMN IS IN ANY INDEX OR FOREIGN KEY — VERIFIED, NOT ASSUMED
 *
 * MySQL forbids a TEXT column in a key specification without a prefix length:
 * `ERROR 1170 (42000) BLOB/TEXT column 'b' used in key specification without a key
 * length`. That would be a BLOCKER for this migration, so it was checked, twice,
 * and the checks are recorded here so the next reader does not re-derive them.
 *
 * **1. Declared keys, read from the three create migrations.** All three tables
 * declare the SAME two constraints, and neither mentions `bytes`:
 *
 *     $table->primary(['account_id', 'id', 'position']);          // integral only
 *     $table->foreign(['account_id', 'id'])
 *         ->references(['account_id', 'id'])
 *         ->on('tf_documents')                    // (tf_photos / tf_sticker_sets)
 *         ->onDelete('cascade');
 *
 * `account_id` is `unsignedBigInteger`, `id` is `bigInteger`, `position` is
 * `smallInteger` — all integral, so neither key can pull in a TEXT column.
 *
 * **2. Every mention of a `bytes` column across this whole migration directory**,
 * to catch a LATER migration that adds an index the create files do not have:
 *
 *     $ grep -rn "'bytes'" src/Laravel/Migrations/*.php
 *     2026_09_14_200020_create_tf_documents_tables.php:68:  $table->string('bytes', 2048)->default(DB::raw("('')"));
 *     2026_09_14_200021_create_tf_photos_tables.php:36:      $table->string('bytes', 2048)->default(DB::raw("('')"));
 *     2026_09_14_200023_create_tf_sticker_sets_tables.php:56: $table->string('bytes', 2048)->default(DB::raw("('')"));
 *     2026_09_24_000100_create_tf_store_backup_runs_table.php:37: $table->unsignedBigInteger('bytes');
 *
 * Exactly four hits. Three are the declarations this file widens; the fourth is
 * `tf_store_backup_runs.bytes`, a DIFFERENT column meaning "bytes written" and
 * already an integer. No migration anywhere in this directory indexes or
 * constrains a `bytes` column.
 *
 * **3. And the engine agrees, directly.** MEASURED, this run:
 *
 *     CREATE TABLE t2 (a INT, b VARCHAR(64), KEY k (a,b));
 *     ALTER TABLE t2 MODIFY b TEXT;
 *     ERROR 1170 (42000) at line 4: BLOB/TEXT column 'b' used in key specification without a key length
 *
 *     CREATE TABLE t3 (a INT, b VARCHAR(64));      -- no key at all
 *     ALTER TABLE t3 MODIFY b TEXT;
 *     T3 ALTER OK (non-key column)
 *
 * So 1170 is a live hazard in general and does not apply to these three tables.
 * **If a future migration ever indexes one of these `bytes` columns, THIS migration
 * is not what breaks — that one is, and it must give the column a prefix length.**
 *
 * ## CROSS-ENGINE: MySQL 8, Postgres, SQLite
 *
 * The operation is a column retype, and all three engines spell it.
 *
 *   - **MySQL 8.4.11** — `ALTER TABLE ... MODIFY bytes TEXT NOT NULL DEFAULT ((''))`.
 *     MEASURED above, `Query OK`.
 *   - **Postgres** — a `text` retype is `ALTER TABLE ... ALTER COLUMN bytes TYPE
 *     text`, emitted by the same grammar call; `USING` is unnecessary because the
 *     source type is a character type and the target is a character type. A
 *     `varchar(n)` -> `text` retype is a widening cast Postgres performs
 *     implicitly for every stored value.
 *   - **SQLite** — SQLite has no `ALTER COLUMN ... MODIFY` AT ALL. `sqlite` does
 *     not parse the statement; MEASURED here, a raw `PDO::exec('ALTER TABLE t
 *     MODIFY bytes TEXT')` returns:
 *         PDOException: SQLSTATE[HY000] General error: 1 near "MODIFY": syntax error
 *     Laravel does not emit raw SQL for a `change()`. It emits a
 *     `Blueprint::change`, and `SQLiteGrammar::compileChange()`
 *     (`vendor/laravel/framework/.../Schema/Grammars/SQLiteGrammar.php:374`)
 *     is deliberately a NO-OP, because the change is folded into Laravel's
 *     12-step table rebuild: create temp table -> copy -> drop original ->
 *     rename -> recreate indexes -> re-enable foreign keys (same file, the
 *     `alter table` block ending at line 373). The rebuild preserves the column's
 *     POSITION and every value, and re-creates the primary key and foreign keys
 *     from the schema, so the shape used by the shipped dial survives it.
 *
 * **The honest limit, stated because it is the kind of thing a later reader would
 * otherwise assume away:** the SQLite claim here is about the OPERATION being
 * expressible and lossless, and it is argued from Laravel's own grammar plus a
 * measured SQLite parse failure of the raw form. It is NOT a claim that sqlite can
 * prove the capacity. **SQLite does not enforce `VARCHAR(n)` length at all** —
 * measured in this repository, twice, and recorded in
 * `MirrorFactWriter::VALUE_NOT_STORABLE_SQLSTATES`'s docblock ("a 40-char and a
 * 400-char value both landed in a `varchar(8)` column without raising") and in
 * `tests/Mirror/MirrorBytesColumnEncodingTest.php`. So the sqlite track's job in
 * this file is ONLY "does not break"; the capacity proof is MySQL's alone.
 *
 * ## `down()` GENUINELY REVERTS — AND REFUSES TO TRUNCATE SILENTLY
 *
 * `down()` puts the column back to `string('bytes', 2048)` with the same
 * `DB::raw("('')")` default and the same `NOT NULL`, so a `migrate:rollback` on a
 * database that never grew past 1024 raw bytes restores the shipped declaration
 * EXACTLY.
 *
 * The interesting case is a database that DID grow. Narrowing a TEXT column back
 * to `VARCHAR(2048)` with an 8000-character value stored behaves in TWO DIFFERENT
 * WAYS, and the difference is the whole reason this method is longer than three
 * lines. MEASURED on MySQL 8.4.11, this run, both against the same table holding
 * an 8000-hex-character value:
 *
 *     -- strict sql_mode (this server's DEFAULT — verified, not assumed):
 *     --   ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,
 *     --   ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION
 *     ALTER TABLE d MODIFY bytes VARCHAR(2048) NOT NULL;
 *     ERROR 1406 (22001) at line 1: Data too long for column 'bytes' at row 1
 *     exit 1
 *
 *     -- non-strict sql_mode:
 *     SET SESSION sql_mode='';
 *     ALTER TABLE d MODIFY bytes VARCHAR(2048) NOT NULL;
 *     exit 0                          <-- NO ERROR AT ALL
 *     SELECT a, LENGTH(bytes) AS after_len FROM d;
 *         a: 1
 *         after_len: 2048             <-- SILENTLY TRUNCATED
 *
 * So the naive `down()` is correct on a strict server and CATASTROPHICALLY SILENT
 * on a non-strict one: 8000 hex characters become 2048, the document's
 * `file_reference` is destroyed, and the mirror will happily serve a document that
 * can no longer be downloaded — the same silent-disagreement failure as the
 * original defect, but self-inflicted and harder to spot, because a truncated
 * `file_reference` is still a syntactically valid hex string.
 *
 * Therefore `down()` PRE-FLIGHTS: it counts, per table, the rows whose stored
 * `bytes` is longer than the 2048 characters it is about to impose, and if any
 * exist it THROWS a `RuntimeException` naming the table, the count, the ceiling
 * and what to do — rather than performing a destructive ALTER that may or may not
 * fail depending on the server's `sql_mode`. The row count is read through the
 * schema builder's connection, so it is engine-neutral SQL (`LENGTH`/`octet_length`
 * both work on the text types all three engines use).
 *
 * **Why THROW and not TRUNCATE.** Truncating would be a silent, unrecoverable loss
 * of Telegram state, performed by a `down()` that an operator runs expecting a
 * mechanical revert. A thrown exception is recoverable, names the blocker, and
 * leaves the database untouched. This is the same principle
 * `MySqlDialPortabilityTest` encodes as MySQL-1101 and MySQL-1170: a rule that can
 * only be broken silently gets checked before the operation, not after.
 *
 * **THE PRE-FLIGHT ITSELF IS A CORRECTNESS CLAIM, AND IT WAS MEASURED.** The
 * obvious spelling — `$connection->table($t)->max($column) <= 2048` — is not a
 * weaker guard, it is a NON-GUARD, and it fails in the one direction that matters.
 * `max()` over a character column is a LEXICOGRAPHIC maximum on MySQL and on
 * Postgres alike. MEASURED on MySQL 8.4.11, this run, against a TEXT column
 * holding exactly the two rows that expose it — an 8000-character value starting
 * `'0'`, and a 2-character value `'ff'`:
 *
 *     MAX(bytes)  [LEXICOGRAPHIC] = 'ff...'  (strlen 2)
 *     MAX(LENGTH(bytes))          = 8000
 *     COUNT(LENGTH(bytes) > 2048) = 1
 *
 *     VERDICT: a `->max($column) <= 2048` guard compares against a string of
 *     length 2 -> it would PASS and narrow anyway, silently destroying the
 *     8000-character value. The shipped `MAX(LENGTH(bytes))` pre-flight
 *     returns 8000 -> it correctly THROWS.
 *
 * `'f'` > `'0'`, so the two-character row wins a string comparison and the
 * eight-thousand-character row is invisible to it. This is not a hypothetical
 * ordering: hex values starting `'0'`–`'9'` are the common case, since every
 * other byte value must sort above them for the bug to bite.
 *
 * The other half of the obvious spelling is a HARD ERROR on Postgres:
 * `->where($column, '>', 2048)` binds 2048 as an integer, and Postgres has no
 * `text > integer` operator, so a `down()` written that way cannot roll back on
 * Postgres AT ALL. `length()` is portable; the column itself is not comparable
 * to a number portably.
 *
 * `LENGTH()` is a BYTE count on MySQL and a CHARACTER count on Postgres and
 * SQLite. Those agree HERE — and only here — because the column stores lowercase
 * hex, which is pure ASCII, so one byte is exactly one character. That property
 * is what makes a single portable expression correct on all three engines
 * instead of right on one.
 *
 * **The pre-flight is a CONNECTION-SIDE length check, not a `SELECT *`.** It reads
 * the maximum stored LENGTH rather than the values, so it does not pull a whole
 * `TEXT` column into memory. The length is taken with `LENGTH()` and the
 * aggregate with `max(...)` around it, because both halves of the obvious
 * shorter spelling are wrong on at least one supported engine: a bare
 * `max(bytes)` is a LEXICOGRAPHIC maximum on MySQL and Postgres alike, and
 * `where bytes > 2048` is `text > integer`, an operator Postgres does not
 * have. `down()` therefore throws on all three engines or on none, rather
 * than rolling back correctly on MySQL and fatally on Postgres. `LENGTH()` is
 * a byte count on MySQL and a character count on Postgres and SQLite; those
 * agree here BECAUSE the column stores lowercase hex, which is pure ASCII, so
 * one byte is exactly one character. That property is what makes one portable
 * expression correct on three engines.
 *
 * The pre-flight is deliberately counted on the SAME expression as the guard,
 * so the number in the exception message cannot disagree with the number that
 * triggered it.
 *
 * ## ADDITIVE, PER THIS REPO'S CONVENTION
 *
 * The three create migrations are NOT edited in place. An install that already ran
 * `2026_09_14_200021` has it recorded as applied; editing the file would leave the
 * column `varchar(2048)` on every existing database forever, while making a fresh
 * install correct — which is the worst of both worlds, because the defect is then
 * invisible until it silently loses a row. Same convention and same reasoning as
 * `2026_10_04_000100_make_user_id_unsigned_unique.php`,
 * `2026_10_04_001100_widen_tf_dialog_filters_id_to_bigint.php` and
 * `2026_10_05_000100_fold_saved_star_gift_num_onto_the_saved_gift_fact.php`.
 *
 * ## PROOF
 *
 * `tests/MySql/TfBytesColumnsAreUnboundedOnMysqlTest.php` (MySQL track,
 * `TELEFRAME_MYSQL=1`). Proof 1 inserts 4000 raw bytes — 8000 hex characters, at a
 * stride coprime with 256 so no truncation offset is invisible — into each of the
 * three tables and requires `strlen($readBack) === strlen($hex)` AND
 * `$readBack === $hex`. Proof 2 reads `CHARACTER_MAXIMUM_LENGTH` off
 * `information_schema` and requires `>= 65535`. The exact round trip was
 * reproduced directly against this engine in this run:
 *
 *     SET @hex = REPEAT('0123456789abcdef', 500);   -- 8000 chars = 4000 raw bytes
 *     SELECT LENGTH(@hex);                          -- 8000
 *     INSERT ... ; SELECT LENGTH(bytes), bytes = @hex FROM tf_documents_thumbs WHERE id=2\G
 *         read_back_len: 8000
 *         byte_identical: 1
 */
return new class extends Migration
{
    /**
     * The three `bytes` columns this migration owns, each with the table that
     * declares it.
     *
     * @var list<array{table: string, column: string}>
     */
    private const COLUMNS = [
        ['table' => 'tf_documents_thumbs', 'column' => 'bytes'],
        ['table' => 'tf_photos_sizes', 'column' => 'bytes'],
        ['table' => 'tf_sticker_sets_thumbs', 'column' => 'bytes'],
    ];

    /**
     * The shipped ceiling, in CHARACTERS — `2048`, which is 1024 raw bytes
     * because the stored form is hex at two characters per raw byte.
     *
     * @var int
     */
    private const SHIPPED_LENGTH = 2048;

    public function up(): void
    {
        $this->eachPresentColumn(function (string $table, string $column): void {
            Schema::table($table, function (Blueprint $blueprint) use ($column): void {
                // `change()` re-declares the WHOLE column from this Blueprint, so
                // nullability and the default are stated here deliberately. A bare
                // `->text($column)` would drop NOT NULL on MySQL — measured, see
                // the file docblock.
                $blueprint->text($column)
                    ->default(DB::raw("('')"))
                    ->change();
            });
        });
    }

    public function down(): void
    {
        // PRE-FLIGHT. See the file docblock: narrowing a TEXT column over an
        // oversized value raises 1406 on a strict server (safe) and TRUNCATES
        // SILENTLY on a non-strict one (destroys `file_reference`). Throwing
        // before the ALTER makes the outcome identical on both, and leaves the
        // data intact so the operator can re-run up() or clear the rows.
        $this->eachPresentColumn(function (string $table, string $column): void {
            $connection = Schema::getConnection();
            $grammar = $connection->getQueryGrammar();

            // The comparison is on the LENGTH, never on the column itself, and
            // that is not a style preference — it is the difference between a
            // correct pre-flight on all three engines and a broken one on two:
            //
            //   - `->max($column)` emits `select max(`bytes`)`, which is a
            //     LEXICOGRAPHIC max on both MySQL and Postgres. `'ff'` sorts
            //     ABOVE 8000 characters of `'0123456789abcdef…'`, so the guard
            //     would report "no oversized rows" on exactly the tables that
            //     have them. The `max()` a reader expects is not the `max()`
            //     they get.
            //   - `->where($column, '>', 2048)` emits `where `bytes` > ?` with
            //     2048 bound as an integer. MySQL coerces a string against a
            //     number and answers; POSTGRES REJECTS IT — `text > integer`
            //     is `operator does not exist: text > integer`, a hard error, so
            //     a `down()` written that way cannot roll back on Postgres at
            //     all.
            //
            // So both go through `length()`, which is portable across MySQL 8,
            // Postgres and SQLite. It is a CHARACTER count on Postgres and
            // SQLite and a BYTE count on MySQL — and those agree here, because
            // the column stores lowercase hex, which is pure ASCII, so one byte
            // is one character. That property is what makes this one expression
            // correct on three engines instead of right on one.
            //
            // The aggregate is `max(length(...))` rather than a `length(max(...))`,
            // so the answer is a number and not a string PHP would have to parse.
            $wrapped = $grammar->wrap($column);

            $longest = $connection->table($table)
                ->selectRaw('MAX(LENGTH('.$wrapped.')) AS max_length')
                ->value('max_length');

            if ($longest === null || (int) $longest <= self::SHIPPED_LENGTH) {
                return;
            }

            $rows = $connection->table($table)
                ->whereRaw('LENGTH('.$wrapped.') > ?', [self::SHIPPED_LENGTH])
                ->count();

            throw new RuntimeException(sprintf(
                'Refusing to narrow %s.%s from TEXT back to varchar(%d): %d row(s) hold a value longer than %d '
                .'characters (%d raw bytes at hex encoding, which is how the column is stored), and narrowing it '
                .'either raises MySQL 1406 on a strict server or SILENTLY TRUNCATES on a non-strict one — both '
                .'destroy a Telegram document reference that the mirror will then serve as if it were intact. '
                .'No ALTER was run. Clear or re-ingest those rows first, then roll back.',
                $table,
                $column,
                self::SHIPPED_LENGTH,
                $rows,
                self::SHIPPED_LENGTH,
                intdiv(self::SHIPPED_LENGTH, 2),
            ));
        });

        $this->eachPresentColumn(function (string $table, string $column): void {
            Schema::table($table, function (Blueprint $blueprint) use ($column): void {
                // The exact shipped declaration: string('bytes', 2048) with the
                // DB::raw expression default, NOT NULL. See the file docblock for
                // why the default keeps its parenthesised spelling here too.
                $blueprint->string($column, self::SHIPPED_LENGTH)
                    ->default(DB::raw("('')"))
                    ->change();
            });
        });
    }

    /**
     * Run `$work` once per owned column that is actually present.
     *
     * Guarded on `hasTable`/`hasColumn` in both directions, which is this
     * family's established style (`2026_09_14_000000`, `2026_09_24_000101`,
     * `2026_09_26_000100`, `2026_10_04_000500`): a re-run of `up()`, or a second
     * `down()`, is a no-op instead of a duplicate-column or missing-column error.
     * A partially-migrated install is also survivable, which is the case this
     * guard exists for — an operator rolling back one step must not be stopped by
     * a table the previous steps never created.
     *
     * @param  callable(string, string): void  $work  receives ($table, $column)
     */
    private function eachPresentColumn(callable $work): void
    {
        foreach (self::COLUMNS as $target) {
            if (! Schema::hasTable($target['table'])) {
                continue;
            }

            if (! Schema::hasColumn($target['table'], $target['column'])) {
                continue;
            }

            $work($target['table'], $target['column']);
        }
    }
};
