<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Identity;

use Illuminate\Database\Eloquent\Model;
use MeRezaRezaei\Teleframe\Ingest\Events\UpdateStored;

/**
 * Q8 write-path + Q7 read-path repository for `tl_user_bindings`.
 *
 * A deliberately tiny static façade over the Eloquent model: every method is
 * stateless and container-free so it runs in Laravel AND plain-PHP apps.
 * Design ruling: the model owns identity lookups (`TlUserBinding::bindingFor`
 * — the single Q7 tenancy point), this class owns the two WRITE hooks the
 * roadmap names (UpdateStored + login completion) plus the two public DX
 * surface methods (findTF, bindLaravelUser), and the traits/guards all
 * delegate here so tenancy behaviour is enforced in exactly one place.
 */
final class Bindings
{
    /**
     * Q8 primary write API: attach a Laravel User to a telegram identity.
     * accountId omitted => recorded on the primary account (default) tenant.
     * Idempotent: re-binding the same (tg id, account) updates the morph,
     * never duplicates (unique (tl_user_id, account_id) is the backstop).
     */
    public static function bindLaravelUser(Model $user, int $tgId, ?int $accountId = null): TlUserBinding
    {
        return TlUserBinding::ensureBinding($tgId, $accountId, $user);
    }

    /**
     * Q7 dev-facing lookup: the binding for (telegram id, primary-account by
     * default; explicit accountId overrides). Returns null when the account
     * cannot be resolved OR the row is absent — never a cross-tenant scan.
     */
    public static function findTF(int $tgId, ?int $accountId = null): ?TlUserBinding
    {
        return TlUserBinding::bindingFor($tgId, $accountId);
    }

    /**
     * Q11/Q8 write-hook fired on `UpdateStored` (truth-first uprate DTO —
     * carries the root model + account; there is deliberately NO second
     * raw-update DTO). Registers/warms the binding for the event's telegram
     * principal WITHOUT fabricating a Laravel User association:
     *
     * - root model exposes a stable telegram id exactly ? (a user-namespace
     *   root instance `tl_id`, or a numeric `tl_user_id` value attribute) =>
     *   the (tl_user_id, account_id) binding row is ensured and its
     *   `contact_lost` flag is cleared — a stored update is hard evidence the
     *   identity is alive on that account.
     * - otherwise no-op: message-rooted updates carry anonymized UUID refs in
     *   this mirror, not writer tg ids, and the binding must never guess.
     */
    public static function onStoreUpdate(UpdateStored $event): ?TlUserBinding
    {
        $tgId = self::telegramUserIdFromModel($event->model);
        if ($tgId === null) {
            return null;
        }

        $binding = TlUserBinding::ensureBinding($tgId, $event->accountId);

        if ($binding->contact_lost) {
            $binding->markContactLost(false);
        }

        return $binding;
    }

    /**
     * Q8/Q11 login-completion write-hook: register the telegram identity the
     * user-app logged in as (primary account by default) BEFORE any Laravel
     * User attaches. The app calls bindLaravelUser()/HasUserTelegram at the
     * same point when the Web-User is known.
     */
    public static function onLogin(int $tgId, ?int $accountId = null): TlUserBinding
    {
        return TlUserBinding::ensureBinding($tgId, $accountId);
    }

    /**
     * Passport authentication-provenance write-hook: stamp the binding row of
     * a completed Telegram Passport login with WHEN it happened and WHICH
     * field TYPES were verified.
     *
     * WHY THE BINDING ROW AND NOT A NEW TABLE. The audit question — "which of
     * my users authenticated via Telegram, and with what proof?" — is already
     * a question about this row: it is the one record that ties a Laravel user
     * to a telegram identity per tenant, and `BindingWriter`/`EloquentBindingWriter`
     * already ship the explicit ruling that the binding row IS the passport
     * identity and no new table is ever added for it. A separate provenance
     * table would duplicate that tenancy (its own account_id, its own unique
     * key, its own join) to answer a question the binding row can answer.
     *
     * The binding row also has the property an AUDIT trail needs most: it
     * deliberately outlives the instance and carries no foreign key, so
     * deleting a Laravel User does not erase the record that the account once
     * authenticated through Telegram. Provenance on the host's own `users`
     * table was rejected outright — that table is host-owned, and the package
     * never writes to it.
     *
     * TYPES ONLY. `$fieldsVerified` is a list of SecureValueType names; the
     * decrypted values are never passed in, never stored, and never logged.
     *
     * Idempotent, like every other write-hook here: re-stamping an existing
     * row overwrites the provenance rather than appending a second record, so
     * the columns always describe the MOST RECENT passport login.
     *
     * Throws if the bindings table is unreachable (a plain-PHP host that never
     * ran the identity migration); the login has already succeeded by this
     * point, so `EloquentBindingWriter::recordProvenance()` catches and
     * discards that rather than turning completed authentication into a
     * logout. Provenance is never allowed to break a login.
     *
     * @param  list<string>  $fieldsVerified  SecureValueType NAMES, never values
     */
    public static function recordPassportLogin(
        int $tgId,
        ?int $accountId = null,
        array $fieldsVerified = [],
    ): TlUserBinding {
        // Look the binding up first, and ensure one if the login reached this
        // point before the row existed: provenance always has a row to land
        // on, whether or not a prior write created it.
        $binding = TlUserBinding::bindingFor($tgId, $accountId);

        if ($binding === null) {
            $binding = TlUserBinding::ensureBinding($tgId, $accountId);
        }

        return $binding->recordPassportProvenance($fieldsVerified);
    }

    /**
     * Exact-typed extraction rule for the UpdateStored write-hook: the
     * telegram identity of the event's root instance model, or null when the
     * mirror does not expose it directly. String-table/heuristic-free on
     * purpose (zero-regex; the engine trusts exact columns, never guesses).
     */
    public static function telegramUserIdFromModel(Model $model): ?int
    {
        $value = null;

        if (str_starts_with($model->getTable(), 'tl_user') && $model->getAttribute('tl_id') !== null) {
            $value = $model->getAttribute('tl_id');
        }

        if ($value === null && $model->getAttribute('tl_user_id') !== null) {
            $value = $model->getAttribute('tl_user_id');
        }

        return $value === null ? null : (int) $value;
    }
}
