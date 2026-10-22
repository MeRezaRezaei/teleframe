<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Tenant-key parent fix: `telegram_accounts.user_id` becomes UNSIGNED and
 * UNIQUE.
 *
 * ## WHAT THIS UNBLOCKS
 *
 * The foreign-key plan wants every `tf_*.account_id` — declared
 * `unsignedBigInteger`, so `BIGINT UNSIGNED` — to carry a CASCADE FOREIGN KEY
 * to `telegram_accounts(user_id)`. As shipped, `user_id` is `bigInteger`
 * (signed `BIGINT`) and MySQL refuses the constraint outright. Measured on
 * MySQL 8.4.11, verbatim:
 *
 *   ERROR 3780 (HY000): Referencing column 'account_id' and referenced column
 *   'user_id' in foreign key constraint 'account_tenant_fk' are incompatible.
 *
 * Fixing the PARENT rather than the 265 children is candidate A of
 * `docs/decisions.md` D-005; the alternatives (265 signed ALTERs, a real
 * carrier column, a STORED generated column, writer-side enforcement) are
 * refuted or rejected there, each with a measurement. This migration is
 * candidate A and nothing else. It adds NO child constraint — the 265 land in
 * a later wave, and cannot start before this one.
 *
 * TWO changes, not one, because both are prerequisites. Measured, in order,
 * on MySQL 8.4.11 against this schema:
 *
 *   ERROR 6125 (HY000): Failed to add the foreign key constraint. Missing
 *     unique key for constraint 'account_tenant_fk' in the referenced table
 *     'telegram_accounts'                                  <- needs the UNIQUE
 *   ERROR 3780 (HY000): ... are incompatible.               <- needs UNSIGNED
 *   ... and after both: the constraint is created, a valid row inserts, an
 *     orphan is refused `ERROR 1452 (23000)`, and ON DELETE CASCADE takes the
 *     child from 1 row to 0.
 *
 * ## THE ORDER INSIDE up() IS FORCED: UNIQUE FIRST, THEN THE TYPE
 *
 * `user_id` carries NO uniqueness today, so an existing install can hold
 * duplicates and the UNIQUE will raise `ERROR 1062 Duplicate entry` — and
 * WHICH statement runs first decides whether that leaves a half-applied
 * parent. Both orders measured on 8.4.11 from the same starting point (two
 * rows sharing user_id 111, one NULL):
 *
 *   TYPE then UNIQUE -> ERROR 1062 on the SECOND statement; column reads back
 *                        `bigint unsigned`, index count 0. COMMITTED AND
 *                        BROKEN.
 *   UNIQUE then TYPE -> ERROR 1062 on the FIRST statement; column reads back
 *                        `bigint`, index count 0. NOTHING CHANGED.
 *
 * So this runs UNIQUE-first deliberately. `MODIFY` on a UNIQUE-indexed column
 * is accepted by MySQL 8.4.11 (measured), so the order costs nothing.
 *
 * ## THE TWO GATES, AND WHY THE MIGRATION CHECKS THEM ITSELF
 *
 * The duplicate gate is the REAL gate on this change and is invisible in the
 * schema: nothing constrains `user_id` today. The negative gate is invisible
 * in the CODE, and is the dangerous one:
 *
 *   A negative `user_id` under the shipped strict sql_mode raises
 *     ERROR 1264 (22003): Out of range value for column 'user_id' at row 1
 *   and leaves the row UNCHANGED (-1001234567890 survived).
 *
 *   THE SAME STATEMENT under `sql_mode = ''` DOES NOT RAISE. It succeeds, and
 *   the row silently becomes 0:
 *     | label | h5b_value_after_successful_alter |
 *     | neg   | 0                                |
 *
 * A `user_id` of 0 is not a Telegram account. It is a destroyed credential
 * mapping, reported as a successful migration. **So this migration does not
 * depend on strict mode being on** — it pre-checks for negatives itself and
 * throws, rather than trusting the session to raise for it. The same holds for
 * duplicates: pre-checked, so the 1062 can never arrive mid-ALTER.
 *
 * Telegram user ids are non-negative because this repository says so —
 * `2026_09_14_200001_create_tf_users_tables.php:15-17` ("telegram user ids are
 * positive"), with chat and channel ids the deliberate counter-example
 * (`2026_09_14_200002_create_tf_chats_tables.php:17`,
 * `2026_09_14_200003_create_tf_channels_tables.php:16`) — which is why the
 * PEER tables are signed and `account_id`, only ever the tenant, is already
 * unsigned. The TL proves nothing: `user_id:long`
 * (`src/Schema/schema/sources/mtproto.tl:3-4`, `api_full.tl:22`) is signed, as
 * is the column being changed here. The justification is the repo's own stated
 * platform knowledge, and the negative gate below is the belt to that braces.
 *
 * ## EXISTING INSTALLS: ADDITIVE, PER THIS REPO'S CONVENTION
 *
 * This is a NEW migration, never an edit to 2026_09_09_000201 or to
 * 2026_09_14_000000. The repo's convention is established by those four
 * additive drift-fix migrations (2026_09_14_000000 and three siblings), each of
 * which ADDS through `Schema::table` behind a `hasColumn` guard so an install
 * that already ran the create is not stranded; editing the create in place
 * would leave every existing install on the old shape forever. This migration
 * follows that convention, with the guard re-pointed at the index rather than
 * the column, so a re-run is a no-op instead of a duplicate-key error.
 *
 * It is also the FIRST type-altering migration in the repository: `->change()`
 * appears zero times in `src/`, `tests/` and `bin/` (measured 2026-10-04), so
 * this file is where that convention starts rather than follows.
 *
 * ## down() IS ONE-WAY, AND SAYS SO
 *
 * Reverting the parent while a tenant FK references `user_id` raises the very
 * ERROR 3780 this migration exists to remove (measured on 8.4.11). Once the
 * later wave has landed, `down()` cannot succeed. It is implemented honestly
 * and documented rather than made to pretend otherwise.
 */
return new class extends Migration
{
    /** The exact constraint name, quoted so a rename shows up as a test failure. */
    private const UNIQUE_INDEX = 'telegram_accounts_user_id_unique';

    public function up(): void
    {
        if (! Schema::hasTable('telegram_accounts') || ! Schema::hasColumn('telegram_accounts', 'user_id')) {
            return;
        }

        $schema = Schema::getConnection()->getSchemaBuilder();

        $this->refuseDuplicateTenants();
        $this->refuseNegativeTenants();

        // UNIQUE FIRST — see the file docblock: type-first commits the type
        // change and then fails on the 1062, which is the half-applied parent.
        if (! $schema->hasIndex('telegram_accounts', self::UNIQUE_INDEX)) {
            Schema::table('telegram_accounts', function (Blueprint $table) {
                $table->unique('user_id', self::UNIQUE_INDEX);
            });
        }

        // Idempotent by effect: MODIFYing an already-unsigned column to
        // BIGINT UNSIGNED is a no-op for the schema. The table is tiny (one
        // row per logged-in account), so the rebuild cost D-005 measured at
        // 0.149 s is not worth a dialect-specific type probe to avoid.
        Schema::table('telegram_accounts', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('telegram_accounts') || ! Schema::hasColumn('telegram_accounts', 'user_id')) {
            return;
        }

        $schema = Schema::getConnection()->getSchemaBuilder();

        if ($schema->hasIndex('telegram_accounts', self::UNIQUE_INDEX)) {
            Schema::table('telegram_accounts', function (Blueprint $table) {
                $table->dropUnique(self::UNIQUE_INDEX);
            });
        }

        // ONE-WAY: once a tenant FK references user_id, reverting the parent
        // raises ERROR 3780 (measured on MySQL 8.4.11). See the docblock.
        Schema::table('telegram_accounts', function (Blueprint $table) {
            $table->bigInteger('user_id')->nullable()->change();
        });
    }

    /**
     * Refuse to proceed when two accounts already share a `user_id`.
     *
     * Nothing in the schema prevents that today, so this is data-dependent and
     * invisible until the UNIQUE meets it. The offending VALUES are named in
     * the message: a bare SQLSTATE leaves a host operator no way to know which
     * rows to reconcile.
     */
    private function refuseDuplicateTenants(): void
    {
        $duplicates = Schema::getConnection()
            ->table('telegram_accounts')
            ->select('user_id')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->limit(10)
            ->get()
            ->map(static fn ($row): int => (int) $row->user_id)
            ->all();

        if ($duplicates === []) {
            return;
        }

        throw new \RuntimeException(sprintf(
            'telegram_accounts.user_id holds duplicate tenant ids (%s), so it cannot be made UNIQUE. Every '
            .'account row is a logged-in Telegram account; two rows sharing a user_id means one of them is '
            .'stale or was entered twice. Reconcile them — keep the row whose session is live, delete the '
            .'other — and re-run. Refusing HERE is deliberate: attempting the UNIQUE first would raise '
            .'ERROR 1062 after the column type had already been changed, leaving a half-migrated parent.',
            implode(', ', $duplicates),
        ));
    }

    /**
     * Refuse to proceed when a `user_id` is negative.
     *
     * This gate exists because the engine cannot be trusted to raise. Under a
     * non-strict `sql_mode` the ALTER SUCCEEDS and clamps the value to 0,
     * destroying the credential mapping without erroring (measured, 8.4.11).
     * See the file docblock for both readings.
     */
    private function refuseNegativeTenants(): void
    {
        $negative = Schema::getConnection()
            ->table('telegram_accounts')
            ->where('user_id', '<', 0)
            ->orderBy('user_id')
            ->limit(10)
            ->pluck('user_id')
            ->map(static fn ($value): int => (int) $value)
            ->all();

        if ($negative === []) {
            return;
        }

        throw new \RuntimeException(sprintf(
            'telegram_accounts.user_id holds negative value(s) (%s). A Telegram user id is never negative '
            .'(the repo states this at 2026_09_14_200001_create_tf_users_tables.php:15-17), so these rows '
            .'are corrupt and this migration refuses to guess which way. They must NOT be migrated: under a '
            .'non-strict sql_mode the ALTER would succeed and silently rewrite them to 0, which is not a '
            .'Telegram account. Remove or correct the rows, then re-run.',
            implode(', ', $negative),
        ));
    }
};
