<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Revocation marker on `telegram_accounts` (2026-09-27).
 *
 * THE GAP THIS CLOSES. A revoked Telegram session used to be an EXCEPTION and
 * nothing else. The row kept its `session` blob, kept decrypting, and kept
 * being handed to every future request by TeleframeClient::userFromVault() —
 * so a dead account stayed indistinguishable from a live one, forever, to
 * every layer of a host app. Nothing in the database could answer the only
 * question that matters during an incident: WHICH accounts need re-login.
 * The auth-key failure was loud (the poller rethrows it) but it was also
 * anonymous: a revoked key carried no owner.
 *
 * ONE NULLABLE TIMESTAMP, NOT A STATUS ENUM. `revoked_at` answers exactly one
 * question — "was this account's key rejected by the server?" — and the
 * answer is monotone: a key the server has deleted cannot come back on its
 * own, only a fresh login can (which writes a new session and clears the
 * marker). A `status` column would have to enumerate states nobody asked for
 * (pending / half-broken / ...), and every invented state is a promise to
 * keep. `revoked_at IS NULL` is the healthy answer, so the discriminator
 * needs no second column.
 *
 * The column stores no secret: a moment in time, never the session value, the
 * key id, or the RPC error text. A revoked row is otherwise untouched — the
 * session blob is deliberately NOT blanked here, so an operator who is not
 * ready to re-login keeps whatever diagnostic value it still has, and so
 * down()/rollback cannot destroy data. The refusal lives in the read path
 * (TeleframeClient::resolveVaultAccount refuses a revoked account by name)
 * rather than in a write that would be irreversible.
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
 * or without --prune. The dial ships migrations for TL-schema tables only,
 * and `telegram_accounts` is not part of the Telegram schema — there is no
 * generator that could reproduce this column.
 *
 * Both operations are guarded so the migration is re-runnable: a host whose
 * table does not exist (plain-PHP, partial install) is a no-op, and a host
 * that already has the column is not altered. `down()` removes only what this
 * migration added.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('telegram_accounts')) {
            return; // plain-PHP host without the credential vault: nothing to extend
        }

        if (Schema::hasColumn('telegram_accounts', 'revoked_at')) {
            return;
        }

        Schema::table('telegram_accounts', function (Blueprint $table) {
            // WHEN the server rejected this account's auth key. Null = the
            // session has never been observed revoked. A fresh login clears
            // it (TelegramAccount::markRevoked() is the only writer).
            $table->timestamp('revoked_at')->nullable()->after('dc_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('telegram_accounts')) {
            return;
        }

        if (! Schema::hasColumn('telegram_accounts', 'revoked_at')) {
            return;
        }

        Schema::table('telegram_accounts', function (Blueprint $table) {
            $table->dropColumn('revoked_at');
        });
    }
};
