<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * The sixteen `tf_saved_star_gifts_*` child key columns become BIGINT.
 *
 * ## THE DEFECT, IN ONE LINE
 *
 * Every one of the sixteen is declared `$table->string($key, 255)` by the
 * shared `factKey()` helper
 * (2026_09_14_200040_create_tf_stars_business_tables.php:559-563), while the
 * column actually carries a TL `long`. `ResolvedColumnsMatchShippedDdlTest`
 * measured all sixteen as "resolver emits bigint (BIGINT), shipped DDL declares
 * text".
 *
 * ## WHY THE DDL IS THE WRONG SIDE HERE — THE WIRE IS THE AUTHORITY
 *
 * All sixteen tables are direct children of `tf_saved_star_gifts`, and the key
 * they share is the `id` of the required `StarGift` union:
 *
 *   savedStarGift#41df43fc ... gift:StarGift ...          (api_full.tl:2024)
 *   starGift#313a9547    ... id:long sticker:Document ... (api_full.tl:1974)
 *   starGiftUnique#85f0a9cd ... id:long gift_id:long ...  (api_full.tl:1975)
 *
 * Both arms of the union declare `id:long`, so there is exactly ONE correct
 * type for all sixteen columns and it is a signed 64-bit integer. Nothing here
 * is a judgement call between sixteen different answers: `id` is not a
 * per-table field at all, it is the single parent key sixteen child tables
 * hang off, and the union's two constructors agree on its type.
 *
 * This is the OPPOSITE disposition from the thirty-four string-keyed
 * divergences closed in 0aeeafc6, where the wire said `id:string` and the DDL's
 * TEXT was right to stay. There the resolver had to change; here the shipped
 * DDL is the outlier and the DDL changes.
 *
 * Note that `tf_saved_star_gifts_gift` — the union flattened as a 1:1 child —
 * drops the union's own `id` precisely because it IS the parent key
 * (2026_09_14_200040_create_tf_stars_business_tables.php:582-585), which is
 * why that table's key column needs the same correction as the other fifteen.
 *
 * ## WHY A NEW FILE AND NOT AN EDIT TO 200040
 *
 * 2026_09_14_200040 is SHIPPED: it is present at tags v1.3.0, v1.3.1 and
 * v1.3.2 (verified with `git cat-file -e v1.3.0:<path>`). An install that has
 * already run it has it recorded as applied, so editing the create migration in
 * place leaves every existing install on the wrong key type forever — the
 * single failure mode an additive migration exists to avoid. This is the
 * repository's own stated precedent at
 * tests/Schema/ShipDialGoldenTest.php:176-181.
 *
 * (Contrast 2026_09_29_000100, which is in no tag and was therefore safe to
 * edit in place. The two must not be confused.)
 *
 * ## WHY SIGNED, NOT UNSIGNED
 *
 * A TL `long` is a signed 64-bit integer, and the repository does not pretend
 * otherwise: 2026_10_04_000100_make_user_id_unsigned_unique.php changes a
 * `user_id` to unsigned only because the repo states Telegram ids are positive
 * and gates the negatives itself. There is no such statement for a gift id, so
 * the honest reading of the wire is the signed one — which is also the house
 * style for a wire-`long` key in this dial, e.g. the `bigInteger('id')` keys at
 * 2026_09_29_000100_create_tf_mirror_gap_tables.php:130, 243 and 275.
 *
 * ## ALL SIXTEEN ARE IN A PRIMARY KEY, AND THAT IS WHY BIGINT IS SAFE
 *
 * Fifteen are keyed `PRIMARY KEY (account_id, id)`; `tf_saved_star_gifts_collection_id`
 * is keyed `(account_id, id, position)` and additionally carries a secondary
 * index on `(account_id, id)`. Every one of those seventeen index entries
 * currently holds a `varchar(255)`, which on InnoDB with utf8mb4 costs up to
 * 1020 bytes of key length; a BIGINT costs 8. So the correction SHRINKS every
 * affected index rather than growing it, and it moves these keys away from the
 * MySQL-1170 boundary rather than toward it:
 *
 *   - MySQL-1170 rule (a) forbids a TEXT column in a PRIMARY KEY. These columns
 *     are `varchar(255)`, not TEXT, so the rule does not fire today, and after
 *     this migration they are integers, so it still cannot.
 *   - MySQL-1170 rule (b) requires a `string` PK column to be length 255. They
 *     are 255 today; afterwards they are no longer declared with `string` at
 *     all, so the rule has nothing left to police.
 *
 * The scanner that enforces both (tests/Schema/MySqlDialPortabilityTest.php)
 * collects column types and PRIMARY KEY columns only inside a
 * `Schema::create` block (`$inCreateBlock`, lines 387 and 397-404), and this
 * migration uses `Schema::table` throughout — so no primary key is introduced,
 * removed, or rewritten here, and the 1170 rules do not even reach it.
 *
 * MySQL performs the MODIFY with the indexes in place; 2026_10_04_000100
 * already proved a type change under a live UNIQUE index is accepted, so no
 * index is dropped and re-added here.
 *
 * ## WHY up() PRE-CHECKS ITSELF INSTEAD OF TRUSTING strict mode
 *
 * This is a narrowing conversion (text column to integer column), so it is the
 * one direction where a value can be lost, and 2026_10_04_000100 measured that
 * under a non-strict sql_mode MySQL 8.4.11 does NOT raise on a bad value — it
 * succeeds and coerces. A silently rewritten gift key would be a wrong row that
 * reports success. So `refuseUnconvertibleKeys()` scans every affected column
 * first, in PHP, and throws with the offending values NAMED rather than letting
 * a bare SQLSTATE reach the operator.
 *
 * The scan is strictly cheaper than the migration itself: the ALTER rebuilds
 * every one of these tables anyway, so a full pass over the key column adds
 * nothing to the cost of the change it is protecting.
 *
 * down() IS HONEST ABOUT WHAT IT DOES: it returns the column to the shipped
 * varchar(255) and the key values convert back cleanly, so it is implemented
 * rather than made to pretend.
 */
return new class extends Migration
{
    /**
     * The one column all sixteen share: the parent key `StarGift.id`.
     */
    private const KEY_COLUMN = 'id';

    /**
     * The sixteen write-reachable direct children of `tf_saved_star_gifts`, in
     * the declaration order of 200040 so a diff against that file reads top to
     * bottom. `tf_saved_star_gifts` itself is deliberately absent: it is the
     * parent and carries no key column of its own (200040:358-372).
     *
     * @var list<string>
     */
    private const TABLES = [
        'tf_saved_star_gifts_gift',
        'tf_saved_star_gifts_from_id',
        'tf_saved_star_gifts_message',
        'tf_saved_star_gifts_msg_id',
        'tf_saved_star_gifts_saved_id',
        'tf_saved_star_gifts_convert_stars',
        'tf_saved_star_gifts_upgrade_stars',
        'tf_saved_star_gifts_can_export_at',
        'tf_saved_star_gifts_transfer_stars',
        'tf_saved_star_gifts_can_transfer_at',
        'tf_saved_star_gifts_can_resell_at',
        'tf_saved_star_gifts_collection_id',
        'tf_saved_star_gifts_prepaid_upgrade_hash',
        'tf_saved_star_gifts_drop_original_details_stars',
        'tf_saved_star_gifts_gift_num',
        'tf_saved_star_gifts_can_craft_at',
    ];

    /** The largest value a signed 64-bit integer column can hold. */
    private const INT64_MAX = '9223372036854775807';

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, self::KEY_COLUMN)) {
                continue;
            }

            // Idempotent by effect: re-running MODIFYs an already-BIGINT column
            // to BIGINT, which changes nothing. A dialect-specific type probe to
            // avoid that is not worth it on sixteen tables, and this is the
            // same trade 2026_10_04_000100 made and documented.
            $this->refuseUnconvertibleKeys($table);

            // 🔴 Postgres REFUSES the cast Laravel emits; MySQL performs it silently.
            //
            // `$blueprint->bigInteger(…)->change()` compiles to
            //     alter table "…" alter column "id" type bigint
            // with NO `USING` clause. The key column is VARCHAR(255) (factKey() at
            // 2026_09_14_200040:559 declares string($key, 255)), and Postgres will not
            // cast varchar -> bigint implicitly:
            //
            //     SQLSTATE[42804]: Datatype mismatch: 7 ERROR:  column "id" cannot be
            //     cast automatically to type bigint
            //     HINT:  You might need to specify "USING id::bigint".
            //
            // MySQL silently widens, so tests/MySql was green — OK (68 tests, 3220
            // assertions) on real 8.4.11 — and stayed green while this migration could
            // not run at all on the other target. Only Postgres could report it.
            //
            // The USING form is valid on BOTH engines, so this is one statement for
            // both rather than a dialect fork. It is emitted only AFTER
            // refuseUnconvertibleKeys() has proven every value is decimal digits inside
            // signed 64-bit — that guard is the only thing stopping a corrupt value
            // being silently rewritten, and it is deliberately left in front.
            $this->retypeWithExplicitCast($table);
        }
    }

    /**
     * Retype `id` to BIGINT with an explicit USING cast.
     *
     * Emits the ALTER directly rather than through `Blueprint::change()`, because the
     * schema builder cannot express a USING clause at all. Kept as its own method so the
     * reason for bypassing the builder is stated next to the code that needs it.
     */
    private function retypeWithExplicitCast(string $table): void
    {
        $quoted = $this->quoteTable($table);

        // 🔴 A THIRD engine exists and I nearly shipped a fix that broke it.
        //
        // My first version branched on `isPostgres()` with MySQL as the else, so SQLite
        // received `ALTER TABLE … MODIFY … BIGINT` — which is MySQL-only syntax and a
        // hard SQL error there:
        //
        //   SQLSTATE[HY000]: General error: 1 near "MODIFY": syntax error
        //   SQL: ALTER TABLE `tf_saved_star_gifts_gift` MODIFY `id` BIGINT
        //
        // `php bin/audit-fidelity.php` boots the shipped dial on SQLite and hit it on the
        // very first run, so the failure was immediate rather than latent — but it was
        // found by the audit failing, not by a gate of mine. **A two-way dialect branch is
        // a three-way bug waiting for the third engine**, and the least-tested engine is
        // the one nobody remembers to enumerate.
        //
        // SQLite accepts NEITHER MODIFY nor a USING clause, and cannot express the column
        // width this migration exists to set — so the honest move there is to use the
        // schema builder, whose dialect handling already picks the right statement, and
        // let the audit measure the shape rather than pretend SQLite can enforce it.
        if ($this->driver() === 'pgsql') {
            DB::statement(sprintf(
                'ALTER TABLE %s ALTER COLUMN %s TYPE BIGINT USING %s::bigint',
                $quoted,
                $this->quoteIdentifier(self::KEY_COLUMN),
                $this->quoteIdentifier(self::KEY_COLUMN),
            ));

            return;
        }

        if ($this->driver() === 'mysql') {
            DB::statement(sprintf(
                'ALTER TABLE %s MODIFY %s BIGINT',
                $quoted,
                $this->quoteIdentifier(self::KEY_COLUMN),
            ));

            return;
        }

        // Any other driver (SQLite): defer to the schema builder, which knows the dialect.
        Schema::table($table, function (Blueprint $blueprint): void {
            $blueprint->bigInteger(self::KEY_COLUMN)->change();
        });
    }

    private function driver(): string
    {
        return strtolower((string) DB::connection()->getDriverName());
    }

    private function quoteTable(string $table): string
    {
        return $this->driver() === 'pgsql'
            ? '"'.str_replace('"', '""', $table).'"'
            : '`'.str_replace('`', '``', $table).'`';
    }

    private function quoteIdentifier(string $identifier): string
    {
        return $this->driver() === 'pgsql'
            ? '"'.str_replace('"', '""', $identifier).'"'
            : '`'.str_replace('`', '``', $identifier).'`';
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, self::KEY_COLUMN)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->string(self::KEY_COLUMN, 255)->change();
            });
        }
    }

    /**
     * Refuse to proceed while any key value cannot survive the narrowing to
     * BIGINT unchanged.
     *
     * Two things disqualify a value: it is not a run of decimal digits (so it is
     * not a TL long at all), or it does not fit in a signed 64-bit integer (so
     * MySQL would refuse it, or coerce it under a non-strict sql_mode — the
     * silent-corruption path measured in 2026_10_04_000100).
     *
     * In practice an install written by this engine cannot hold either: the
     * resolver has always emitted `bigint` for these columns, and the only
     * thing varchar(255) did was hold the digits as text. The gate exists for
     * the install that did not come from this engine, not because a bug is
     * expected — and it names the values so an operator can reconcile rather
     * than guess.
     */
    private function refuseUnconvertibleKeys(string $table): void
    {
        $offenders = [];

        foreach (DB::table($table)->orderBy(self::KEY_COLUMN)->cursor() as $row) {
            $value = (string) $row->{self::KEY_COLUMN};

            if (! $this->fitsSignedInt64($value)) {
                $offenders[$value] = true;
            }

            if (count($offenders) > 9) {
                break;
            }
        }

        if ($offenders === []) {
            return;
        }

        throw new RuntimeException(sprintf(
            '%s.%s holds value(s) (%s) that are not decimal digits inside the signed 64-bit range, so it cannot be '
            .'narrowed to BIGINT. The column carries the StarGift union id (api_full.tl:1974 and :1975, both '
            .'declared `id:long`), so a non-numeric value here is not a gift id — it is corrupt, and this migration '
            .'refuses to guess which way. Do NOT let it be coerced: under a non-strict sql_mode MySQL accepts the '
            .'ALTER and silently rewrites the value (measured on 8.4.11 by 2026_10_04_000100), leaving a wrong key '
            .'behind a successful migration. Delete or correct the rows, then re-run.',
            $table,
            self::KEY_COLUMN,
            implode(', ', array_keys($offenders)),
        ));
    }

    /**
     * Whether this column's value is a decimal integer that fits in a signed
     * 64-bit integer — the exact domain a TL `long` maps onto.
     *
     * The range comparison is done on the digit string rather than by casting
     * to a PHP int, because a cast would silently saturate at PHP_INT_MAX and
     * accept a 19-digit out-of-range value that MySQL then rejects.
     */
    private function fitsSignedInt64(string $value): bool
    {
        if ($value === '' || ! ctype_digit($value)) {
            return false;
        }

        if (strlen($value) < 19) {
            return true;
        }

        return strlen($value) === 19 && strcmp($value, self::INT64_MAX) <= 0;
    }
};
