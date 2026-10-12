<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Retarget the six mis-aimed tenant foreign keys to `telegram_accounts.user_id`.
 *
 * ## THE DEFECT, IN ONE LINE
 *
 * Six shipped constraints declare `account_id → telegram_accounts.**id**` — the
 * vault's APP ROW id — while the engine writes the TELEGRAM USER id. The engine
 * and the constraints disagree about what a tenant is, and the constraints lose:
 * on any connection that enforces them, those six tables refuse every real
 * ingest. Measured in `docs/decisions.md` D-005, "Blocker 2".
 *
 * ## THE SIX, AND THE EXACT LINES THAT DECLARE THEM
 *
 * All six are the identical unnamed statement
 * `$table->foreign('account_id')->references('id')->on('telegram_accounts')->cascadeOnDelete()`,
 * so they carry Laravel's implicit name `{table}_account_id_foreign`:
 *
 * | table | declared at | implicit name dropped by up() |
 * | --- | --- | --- |
 * | `tf_updates` | `2026_09_14_200030_create_tf_updates_tables.php:46` | `tf_updates_account_id_foreign` |
 * | `tf_update_routing` | `2026_09_14_200030_create_tf_updates_tables.php:248` | `tf_update_routing_account_id_foreign` |
 * | `tf_update_state` | `2026_09_14_200030_create_tf_updates_tables.php:301` | `tf_update_state_account_id_foreign` |
 * | `tf_channel_participants` | `2026_09_14_200031_create_tf_channel_participants_tables.php:84` | `tf_channel_participants_account_id_foreign` |
 * | `tf_channel_updates` | `2026_09_14_200031_create_tf_channel_participants_tables.php:157` | `tf_channel_updates_account_id_foreign` |
 * | `tf_update_differences` | `2026_09_14_200031_create_tf_channel_participants_tables.php:197` | `tf_update_differences_account_id_foreign` |
 *
 * They are the ONLY constraints in the curated dial whose parent is
 * `telegram_accounts`; every other shipped FK points at a `tf_*` table
 * (D-005: 54 constraints with `account_id` among the child columns — 48 to a
 * `tf_*` parent, these 6 to `telegram_accounts.id`). That is what makes this
 * migration a six-table edit rather than a 265-constraint sweep.
 *
 * ## WHY RETARGET AND NOT DROP — measured, not reasoned
 *
 * D-005 executed all three dispositions on MySQL 8.4.11 against the shipped
 * column shapes:
 *
 *   leave (as shipped)  -> ERROR 1452 on a real engine row (id=1, user_id=1724372757)
 *   drop                -> admits an ORPHAN; 2 rows inserted, one a fabricated tenant
 *   retarget            -> real engine row ACCEPTED, fabricated tenant REFUSED ERROR 1452,
 *                          ON DELETE CASCADE takes the child from 1 row to 0
 *
 * Retarget is the only disposition that is both correct AND enforced. Dropping
 * would trade an active hazard for an absent safety net, which is the wrong
 * trade in both directions.
 *
 * ## THE ORDER IS FORCED AND ONE-WAY
 *
 * `telegram_accounts.user_id` shipped as `bigInteger()` — SIGNED — while every
 * `tf_*.account_id` is `unsignedBigInteger`. MySQL refuses that pair at DDL
 * time, so the constraint cannot even be CREATED until the parent is unsigned:
 *
 *   ERROR 3780 (HY000): Referencing column 'account_id' and referenced column
 *   'user_id' in foreign key constraint '...' are incompatible.
 *
 * `2026_10_04_000100_make_user_id_unsigned_unique.php` is that parent fix
 * (candidate A of D-005), and this file's timestamp — 000200 — is what orders
 * it after 000100. The ordering is not a convention here, it is the only one
 * that applies. Reverting the parent while this FK lives raises the SAME
 * ERROR 3780, which is why the parent's own `down()` is documented as one-way.
 *
 * The `UNIQUE(user_id)` half of that parent fix is load-bearing here too, not
 * only for the 265 future constraints: MySQL will not reference a column that
 * is not the left-hand side of a unique key, and refuses with
 * `ERROR 6125 ... Missing unique key`. up() asserts the index is present and
 * throws a named error rather than letting 6125 surface unlabelled.
 *
 * ## ADDITIVE, PER THIS REPO'S CONVENTION — AND NO CREATE MIGRATION IS EDITED
 *
 * The alternative would be to fix the six lines in the two create migrations
 * in place. That is forbidden and would be a defect, not a shortcut: an
 * install that already ran those migrations would keep the broken constraint
 * forever, because the migration that declared it is long since recorded as
 * applied. So the drop and the re-add both happen HERE, inside a NEW additive
 * file — the same reasoning, and the same convention, as
 * `2026_10_04_000100_make_user_id_unsigned_unique.php` and the four additive
 * drift-fix migrations before it.
 *
 * ON DELETE CASCADE IS PRESERVED, NOT RE-DERIVED. Every one of the six shipped
 * `cascadeOnDelete()`, and every one of the six re-added here does the same, so
 * deleting a tenant still takes its updates with it. That is behaviour, not a
 * detail: the plan's tenant semantics depend on it.
 *
 * ## THE NEW CONSTRAINTS ARE NAMED EXPLICITLY, THE OLD ONES WERE NOT
 *
 * The six shipped constraints are unnamed, so they exist on MySQL under
 * Laravel's implicit `{table}_account_id_foreign`. up() drops by that name and
 * re-adds under an EXPLICIT name (`{table}_tenant_fk`) so that a later
 * `dropForeign()` can find the constraint by a name a reader can guess, instead
 * of by convention. down() reverses both halves: it drops the explicit name and
 * restores the implicit one pointing at `id`, which is the state the dial
 * shipped in — a `down()` that did anything else would not be a `down()`.
 *
 * up() is IDEMPOTENT BY EFFECT: a table whose `account_id` already references
 * `telegram_accounts.user_id` is left alone, so a re-run is a no-op rather than
 * a duplicate-constraint error.
 *
 * sqlite records no constraint names and no column signedness, so it can both
 * drop and re-add the constraint and cannot detect the signedness prerequisite.
 * That is why the gate that proves this migration works on the real engine is a
 * MySQL probe, not this file's own test suite — and why the migration asserts
 * the parent index rather than the parent type.
 */
return new class extends Migration
{
    /**
     * The child column on all six, and the parent table they reference.
     *
     * Both are quoted rather than repeated six times because they are the two
     * halves of the defect: one column, one parent, six tables.
     */
    private const TENANT_LEG = 'account_id';

    private const TENANT_TABLE = 'telegram_accounts';

    /** The parent column a tenant IS. D-005, and four citations in `src/`. */
    private const TENANT_KEY = 'user_id';

    /** The parent column the six wrongly compared against. Kept for down(). */
    private const WRONG_KEY = 'id';

    /** The UNIQUE index on the parent column, created by the 000100 migration. */
    private const PARENT_UNIQUE = 'telegram_accounts_user_id_unique';

    /**
     * The six tables, each mapped to the EXPLICIT name its retargeted
     * constraint is created under.
     *
     * The key order is the declaration order in the two create migrations, so a
     * diff against those files reads top to bottom.
     *
     * @var array<string, string>
     */
    private const RETARGETED = [
        'tf_updates' => 'tf_updates_tenant_fk',
        'tf_update_routing' => 'tf_update_routing_tenant_fk',
        'tf_update_state' => 'tf_update_state_tenant_fk',
        'tf_channel_participants' => 'tf_channel_participants_tenant_fk',
        'tf_channel_updates' => 'tf_channel_updates_tenant_fk',
        'tf_update_differences' => 'tf_update_differences_tenant_fk',
    ];

    public function up(): void
    {
        if (! Schema::hasTable(self::TENANT_TABLE)) {
            return;
        }

        $this->refuseWithoutParentKey();

        foreach (self::RETARGETED as $table => $constraint) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $declared = $this->tenantConstraintOn($table);

            if ($declared === null) {
                continue;
            }

            // Already retargeted: a re-run is a no-op, not a duplicate key.
            if (array_map('strval', $declared['foreign_columns']) === [self::TENANT_KEY]) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table, $declared, $constraint): void {
                $this->dropTenantConstraint($blueprint, $declared);
                $blueprint->foreign(self::TENANT_LEG, $constraint)
                    ->references(self::TENANT_KEY)
                    ->on(self::TENANT_TABLE)
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::TENANT_TABLE)) {
            return;
        }

        foreach (self::RETARGETED as $table => $constraint) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $declared = $this->tenantConstraintOn($table);

            if ($declared === null || array_map('strval', $declared['foreign_columns']) !== [self::TENANT_KEY]) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($declared): void {
                $this->dropTenantConstraint($blueprint, $declared);
                $blueprint->foreign(self::TENANT_LEG)
                    ->references(self::WRONG_KEY)
                    ->on(self::TENANT_TABLE)
                    ->cascadeOnDelete();
            });
        }
    }

    /**
     * The single constraint on this table whose parent is `telegram_accounts`,
     * read back from the executed schema — or null when there is none.
     *
     * Reading the schema rather than assuming the drop works is what makes
     * `up()` safe on an install that has already been retargeted by hand, and
     * what lets `down()` refuse to touch a table that was never retargeted.
     *
     * @return array<string, mixed>|null
     */
    private function tenantConstraintOn(string $table): ?array
    {
        foreach (Schema::getConnection()->getSchemaBuilder()->getForeignKeys($table) as $foreign) {
            if ((string) ($foreign['foreign_table'] ?? '') === self::TENANT_TABLE) {
                return $foreign;
            }
        }

        return null;
    }

    /**
     * Drop the constraint that is actually there.
     *
     * The name is taken from the schema when the driver reports one (MySQL
     * does; sqlite does not, and `dropForeign(['account_id'])` is what works
     * there). Guessing the name instead of reading it would compile to
     * `drop foreign key tf_updates_account_id_foreign` and raise ERROR 3941 on
     * any install whose constraint was named something else.
     *
     * @param  array<string, mixed>  $declared
     */
    private function dropTenantConstraint(Blueprint $blueprint, array $declared): void
    {
        $name = $declared['name'] ?? null;

        if (is_string($name) && $name !== '') {
            $blueprint->dropForeign($name);

            return;
        }

        $blueprint->dropForeign([self::TENANT_LEG]);
    }

    /**
     * Refuse to proceed unless the parent column carries the unique index the
     * foreign key needs.
     *
     * MySQL will not reference a non-unique column, and reports that as
     * `ERROR 6125 ... Missing unique key for constraint 'tf_updates_tenant_fk'`
     * from inside a compound ALTER — a message that names the constraint this
     * migration had just invented and leaves the operator no idea which PREREQUISITE
     * is missing. Saying so here is the difference between a diagnosable failure
     * and a puzzle. The check is cheap and the parent table is one row per
     * logged-in account.
     */
    private function refuseWithoutParentKey(): void
    {
        $schema = Schema::getConnection()->getSchemaBuilder();

        if ($schema->hasIndex(self::TENANT_TABLE, self::PARENT_UNIQUE)) {
            return;
        }

        throw new RuntimeException(sprintf(
            'telegram_accounts.%s carries no UNIQUE index named %s, so MySQL will refuse the retargeted '
            .'tenant foreign keys with ERROR 6125 (Missing unique key). That index is created by '
            .'2026_10_04_000100_make_user_id_unsigned_unique.php, which must run FIRST: this migration also '
            .'depends on that file for the UNSIGNED half, because a signed parent and an unsigned account_id '
            .'cannot be constrained together at all (ERROR 3780). Run the parent fix, then re-run this one.',
            self::TENANT_KEY,
            self::PARENT_UNIQUE,
        ));
    }
};
