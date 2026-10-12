<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

/**
 * PassportSupport — the plain-PHP bind-key anchor for the passport login
 * module (design §1/§2/G2, AppSupport/BackupSupport precedent). Holds the
 * fixed `teleclient.passport.*` container seams so the Laravel facade, the
 * provider and the CLI command all resolve the same keys without the core
 * module depending on a service provider or an artisan Command class.
 *
 * Keys are opaque wire names (AGENTS.md: fixed `teleclient.*` convention,
 * never renamed): the service key is the facade accessor, the keys seam is
 * the PEM-path key loader, the nonces seam is the one-time issue/consume
 * registry (PSR-16 | Memory), the identity seam resolves the Laravel user,
 * the writer seam is the ONE Eloquent binding touchpoint, and the confirmer
 * seam is the optional SRP/2FA chain (default-off).
 *
 * Cycle 18 added the WIRE seams (scope, wire, importer, exporter, profile,
 * form, wire-login) under the same convention: `auth.importAuthorization#a57a7dad`,
 * `auth.exportAuthorization#e5bfffcd`, `help.getPassportConfig#c661ad08`,
 * `account.getAuthorizationForm#a929597a` and `account.acceptAuthorization#f3ed4c73`
 * are OFFICIAL layer-227 constructors that were merely missing from the
 * hand-curated wire seed (gap analysis §3.1). They are OPTIONAL — the webhook
 * login path needs none of them, and no scope is resolved unless the host binds
 * one.
 *
 * Credentials ruling (design §3/§11): these keys never carry session
 * strings, PEM bodies or tokens — secrets resolve at call time, in-call.
 */
final class PassportSupport
{
    /** Container seam: PassportLoginService (facade accessor; built by the provider). */
    public const SERVICE_KEY = 'teleclient.passport.service';

    /** Container seam: PassportKeyLoader (PEM path → key string, 0600-checked). */
    public const KEY_LOADER_KEY = 'teleclient.passport.keys';

    /** Container seam: NonceRegistry (one-time issue/consume, TTL). */
    public const NONCES_KEY = 'teleclient.passport.nonces';

    /** Container seam: IdentityResolver (decrypted payload → Laravel User). */
    public const IDENTITY_KEY = 'teleclient.passport.identity';

    /** Container seam: BindingWriter (the ONE Eloquent touchpoint). */
    public const WRITER_KEY = 'teleclient.passport.writer';

    /** Container seam: PasswordConfirmer (optional SRP/2FA confirm, default-off). */
    public const CONFIRMER_KEY = 'teleclient.passport.confirmer';

    /*
     * The WIRE seams (Cycle 18, 2026-09-25). Same `teleclient.passport.*`
     * convention, same "fixed, never renamed" rule. The wire family is
     * OPTIONAL: nothing here is required for the webhook login path, and an
     * unconfigured host never resolves a scope, so nothing dials at boot.
     */

    /**
     * Container seam (host-supplied): a live `UserAccountScope` for the
     * passport wire calls. The host binds this — or a scope-resolver closure
     * that returns one — to turn wire login on. Unbound is NOT broken: the wire
     * seam then throws loud on first use ("no scope, no dispatch").
     */
    public const SCOPE_KEY = 'teleclient.passport.scope';

    /** Container seam: PassportWireContract (UserScopePassportWire over SCOPE_KEY). */
    public const WIRE_KEY = 'teleclient.passport.wire';

    /** Container seam: AuthorizationImporter (auth.importAuthorization#a57a7dad). */
    public const IMPORTER_KEY = 'teleclient.passport.importer';

    /** Container seam: AuthorizationExporter (auth.exportAuthorization#e5bfffcd). */
    public const EXPORTER_KEY = 'teleclient.passport.exporter';

    /** Container seam: PassportProfileFetcher (help.getPassportConfig#c661ad08). */
    public const PROFILE_KEY = 'teleclient.passport.profile';

    /** Container seam: AuthorizationFormService (getAuthorizationForm/acceptAuthorization). */
    public const FORM_KEY = 'teleclient.passport.form';

    /** Container seam: PassportWireLogin (the wire login orchestrator). */
    public const WIRE_LOGIN_KEY = 'teleclient.passport.wire-login';

    /**
     * OPTIONAL container seam: a host success hook — a `\Closure(Model):
     * void` that runs AFTER a completed login (binding written) and after
     * the provider's own host-auth ride. This is the "what does my app do
     * when someone logs in with Telegram" seam: fire an event, set a
     * remember-me cookie, sync the avatar. The package still never calls
     * Auth::login itself — that stays the ride's job.
     *
     * Unbound = no extra behaviour (the default). Not listed in seams():
     * an unbound OPTIONAL seam is not a missing seam.
     */
    public const ON_LOGIN_KEY = 'teleclient.passport.on-login';

    /**
     * Every seam this module binds, in registration order — the CLI's `status`
     * table and the standalone-smoke pin both walk this list, so a new seam
     * cannot be added without appearing in the operator's view of the module.
     *
     * @return list<string>
     */
    public static function seams(): array
    {
        return [
            self::KEY_LOADER_KEY,
            self::NONCES_KEY,
            self::IDENTITY_KEY,
            self::WRITER_KEY,
            self::CONFIRMER_KEY,
            self::SERVICE_KEY,
            self::WIRE_KEY,
            self::IMPORTER_KEY,
            self::EXPORTER_KEY,
            self::PROFILE_KEY,
            self::FORM_KEY,
            self::WIRE_LOGIN_KEY,
        ];
    }
}
