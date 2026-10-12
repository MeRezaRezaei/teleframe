<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Passport authentication provenance on `tl_user_bindings` (2026-09-26).
 *
 * THE GAP THIS CLOSES. Until now a Telegram Passport login left the host's
 * users row byte-identical to a password login, and the verified field types
 * were thrown away at the end of the chain. Nothing in the database could
 * answer, after the fact, "which of my users authenticated via Telegram, and
 * with what proof?" — which is the first question anyone asks when they need
 * to audit or revoke Telegram-authenticated sessions.
 *
 * WHY THE BINDING ROW. It is already the one record tying a Laravel user to a
 * telegram identity per tenant, and it deliberately carries NO foreign key and
 * has a `contact_lost` flag instead of a cascade, so it OUTLIVES both the
 * telegram instance and the Laravel user. That is exactly the lifetime an
 * audit record needs: deleting a user must not erase the fact that the
 * account once authenticated through Telegram. A separate provenance table
 * would duplicate the tenancy and require a join to answer a question this
 * row already answers; see Bindings::recordPassportLogin for the full ruling.
 * The host's own `users` table was never an option — it is host-owned and the
 * package does not write to it.
 *
 * TYPES, NEVER VALUES. `passport_fields` holds SecureValueType NAMES only
 * ("email", "phone", "personal_details"), comma-joined. A verified email
 * address or phone number written here would copy personal data into a column
 * nobody expected it in, where it would outlive the request and end up in
 * backups and exports. The list answers "with what proof?" without retaining
 * the proof's contents. TlUserBinding::recordPassportProvenance() enforces the
 * allow-list at the write path, so a value cannot reach this column even if a
 * caller passes one.
 *
 * `passport_verified_at IS NULL` is the "never logged in via Telegram" answer,
 * so the discriminator needs no extra flag column.
 *
 * ---------------------------------------------------------------------------
 * HAND-AUTHORED, AND DELIBERATELY CARRYING NO GENERATOR MARKER.
 *
 * NOTE ON WORDING: the marker SchemaRegenerator::shipMigrations() greps for is
 * a literal string, and it matches ANYWHERE in the file — including inside a
 * comment explaining that the file is not generated. So this comment
 * deliberately spells the marker out rather than quoting it verbatim; do not
 * "tidy" it back into a literal.
 *
 * The curated ship dial only purges files whose contents contain that marker;
 * a file without it that is neither in the ship set nor on the app-owned
 * allow-list is warned about and LEFT IN PLACE. So running
 * `artisan teleframe:regenerate` will not delete or overwrite this file, with
 * or without --prune. The dial also ships migrations for TL-schema tables
 * only, and this column pair is not part of the Telegram schema — there is no
 * generator that could reproduce it. The app-owned allow-list in
 * SchemaRegenerator / ShipDialGoldenTest additionally hard-codes the filenames
 * it must never touch; this file is not on it and does not need to be, because
 * the marker-free branch already preserves it.
 *
 * Both operations are guarded with hasColumn() checks so the migration is
 * re-runnable against a host that already has the columns, and `down()`
 * removes only what this migration added.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tl_user_bindings')) {
            return; // plain-PHP host without the identity table: nothing to extend
        }

        Schema::table('tl_user_bindings', function (Blueprint $table) {
            if (! Schema::hasColumn('tl_user_bindings', 'passport_fields')) {
                // Comma-joined SecureValueType NAMES ("email,phone") — never
                // the decrypted values. Nullable: null = no passport login.
                $table->string('passport_fields', 255)->nullable();
            }

            if (! Schema::hasColumn('tl_user_bindings', 'passport_verified_at')) {
                // WHEN the last passport login completed. Null = the identity
                // has never authenticated through Telegram.
                $table->timestamp('passport_verified_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tl_user_bindings')) {
            return;
        }

        Schema::table('tl_user_bindings', function (Blueprint $table) {
            $columns = array_values(array_filter(
                ['passport_fields', 'passport_verified_at'],
                static fn (string $column): bool => Schema::hasColumn('tl_user_bindings', $column),
            ));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
