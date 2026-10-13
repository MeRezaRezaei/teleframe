<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Vault schema drift fix: the original create migration for telegram_accounts
 * (2026_09_09_000201) shipped without the `user_id` column even though the
 * TelegramAccount model, the login finalize controller and the vault console
 * commands (vault:add-account / vault:list / vault:use-default) all read and
 * write it. Existing installs — where 2026_09_09_000201 already ran — get the
 * column here; fresh installs already have it from the corrected create
 * migration, so this ALTER must stay idempotent (hasColumn guard).
 *
 * ## down() IS A DOCUMENTED NO-OP, AND THE FILE IS ONE-WAY
 *
 * `telegram_accounts.user_id` has TWO creators: the corrected create migration
 * above, and this file. `up()` early-returns when the column already exists, so on
 * a FRESH install this migration adds nothing at all — and a `down()` that dropped
 * `user_id` whenever it found the column would remove the OTHER migration's column
 * on that install, taking a column this file never created along with the ledger
 * row recording a change that did not happen.
 *
 * The blast radius is wider than one column, which is why this is not a "harmless"
 * extra column. `user_id` is the TENANT PARENT KEY: `2026_10_04_000100` adds the
 * `telegram_accounts_user_id_unique` index on it precisely so that the 264 tenant
 * foreign keys `2026_10_04_000700` adds can reference it at all (MySQL refuses a
 * FK to a non-unique parent with ERROR 6125). Dropping the column would strand
 * every one of those constraints, and `2026_10_04_000100`'s own `down()` cannot
 * undo that — it drops the unique INDEX, not the column, and so does nothing to
 * restore a `user_id` that is already gone.
 *
 * So `down()` returns without touching the schema. What a reader LOSES is stated
 * rather than left to be discovered: this column can NEVER be removed by a
 * migration rollback on an install that reaches this file. That is acceptable
 * because the only migration that could remove it — the create migration that owns
 * it — has its own `down()` (`Schema::dropIfExists('telegram_accounts')`), and the
 * column goes with the whole table there. Rolling this file back alone leaves the
 * schema exactly as `up()` left it, which is the true inverse of what `up()` did on
 * the only install shape where it did anything at all.
 *
 * The precedent is in the same family: `2026_10_04_000100_make_user_id_unsigned_unique.php`
 * carries an explicit one-way comment and a documented refusal for the same reason.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('telegram_accounts', 'user_id')) {
            return;
        }

        Schema::table('telegram_accounts', function (Blueprint $table) {
            $table->bigInteger('user_id')->nullable()->after('type');
        });
    }

    /**
     * ONE-WAY: deliberately does nothing. See the file docblock.
     *
     * The `hasColumn` guard this file used to carry was the DEFECT, not the safety
     * it appeared to be: it asked "is the column there?" and answered by removing
     * it, without ever asking whether this migration was the one that put it there.
     */
    public function down(): void
    {
        // Intentionally empty — see the docblock above.
    }
};
