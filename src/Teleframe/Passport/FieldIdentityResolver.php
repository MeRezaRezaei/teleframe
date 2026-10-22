<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

use Illuminate\Database\Eloquent\Model;

/**
 * Field-mode identity resolver: cross-check a Telegram-VERIFIED passport
 * email/phone against the host's users table.
 *
 * This is the second mode `IdentityResolver` documents (design §11 / gap Q1)
 * and the reason `teleframe.passport.user_lookup=field` used to throw
 * "needs FieldIdentityResolver (wave 2)". The seam existed; the
 * implementation did not — so the option was advertised in shipped config and
 * unreachable in code. That is the same class of defect as the config
 * publishing bug: a documented option that does not work.
 *
 * WHY THIS IS THE DEFAULT-FREE MODE. `SessionIdentityResolver` binds a login to
 * a web session that was already authenticated. This one CROSS-CHECKS that
 * same session-bound user against a CLAIM inside a verified Telegram response
 * — so the host MUST have verified that claim before trusting it (an email
 * confirmation, a phone OTP). `passport.require_verified` is the switch that
 * says so, and the provider only builds this resolver when that is on. This
 * class does not re-verify anything: it is handed a payload whose claim came
 * from Telegram's OWN location and does one lookup.
 *
 * ══ WHERE THE CLAIM COMES FROM — §6, AND ONLY §6 ═════════════════════════
 *
 * The verified email and phone are read from `PassportPayload::plainEmail()` /
 * `plainPhone()`, which is the Bot API `EncryptedPassportElement` plaintext
 * in `PassportData.data[]` (an element typed `email` / `phone_number`)
 * handed in by the intake. Per `docs/reference/telegram-passport-official.md` §6 that is where
 * Telegram sends them, and they arrive already verified.
 *
 * The previous version read `secure_data.email.address` /
 * `secure_data.phone.phone_number` and fell back to the decrypted blob's own
 * top-level `email`/`phone`. Per §5 the `secure_data` keys are the document
 * types plus `personal_details` and the `Credentials` JSON is
 * `{secure_data, nonce}`, so NONE of those three coordinates exists in
 * Telegram's protocol. Both are gone from this class, and a payload carrying
 * an identity at one of them resolves null here — and is refused outright by
 * `PassportValueValidator` when it is a `secure_data` key.
 *
 * THE RECONCILED RULE — there is ONE identity rule, not two resolvers with
 * different security properties (2026-09-26):
 *   A CLAIM MAY CONFIRM THE BOUND USER. IT MAY NEVER SELECT ONE.
 * Concretely, two refusals stand in front of the lookup:
 *   1. no bound session (null) → null. This class is handed the id the
 *      consumed nonce was issued for; with no id there is nothing to confirm
 *      and a claim that picks its own account is a takeover, not a login. The
 *      previous version accepted the null, ignored the parameter it was
 *      handed, and resolved the row straight from the EMAIL CLAIM — which is
 *      why the shipped `user_lookup=field` configuration was an account
 *      takeover for any host that bound it.
 *   2. a claim that matches a DIFFERENT account than the bound session → null.
 *      A claim that disagrees with the session it was presented under is
 *      exactly the attack shape, so it is refused even though the lookup found
 *      a real row.
 * So field mode adds evidence, never authority, and `user_lookup=session`
 * remains the default precisely because binding to the session is the strong
 * property (see `SessionIdentityResolver`).
 *
 * SECURITY, and the reason the lookup is deliberately narrow:
 *  - Only the two types the design names are matchable (`email`, `phone`);
 *    anything else resolves to null so the service throws LOUD, rather than
 *    matching on a field nobody intended to be an identity key.
 *  - A payload carrying BOTH is a mismatch signal, not a convenience: it is
 *    refused rather than preferring one, because a payload that disagrees with
 *    itself is evidence of a problem, not of a user.
 *  - The user class must exist and extend Model (class_exists +
 *    is_subclass_of), so a typo'd class name resolves to null instead of
 *    fataling on an unknown symbol — the same plain-PHP guard the session
 *    resolver uses.
 *  - The value is compared by the host's own column and nothing is persisted
 *    here; the payload remains evidence for the login, never storage.
 *
 * Plain-PHP rule: no container, facade, or config() calls. The query seam is a
 * closure injected by the provider, so this class stays constructible with no
 * container booted (the standalone-smoke gate).
 *
 * Zero-regex: string/type functions only.
 */
final class FieldIdentityResolver implements IdentityResolver
{
    /** The only matchable types; a payload with any other type resolves null. */
    public const MATCHABLE = ['email', 'phone'];

    /**
     * @param  class-string<Model>|string  $userClass  the host's User model class
     * @param  string  $emailColumn  users-table column compared against `email`
     * @param  string  $phoneColumn  users-table column compared against `phone`
     * @param  (callable(string $column, string $value): ?Model)|null  $finder  injected
     *                                                                          lookup seam; defaults to an Eloquent where()->first() so a host can
     *                                                                          override the query without this class learning about a container
     */
    public function __construct(
        private readonly string $userClass = '',
        private readonly string $emailColumn = 'email',
        private readonly string $phoneColumn = 'phone',
        private $finder = null,
    ) {}

    public function resolve(PassportPayload $payload, ?int $sessionUserId): ?Model
    {
        // REFUSAL 1 — no session to bind to. The claim cannot select an
        // account, so there is nothing this class is allowed to do: null, and
        // the service throws loud. Never fall through to the field.
        if ($sessionUserId === null) {
            return null;
        }

        // §6 — the verified claims, and NOTHING else. There is no fallback
        // to `secure_data.email` / `secure_data.phone` (locations Telegram
        // never writes, per §5) and none to the decrypted blob's own
        // top-level `email`/`phone` (the `Credentials` JSON is
        // `{secure_data, nonce}`). A payload whose only identity lives at
        // either of those therefore resolves null here, and the service
        // throws loud.
        $email = $payload->plainEmail();
        $phone = $payload->plainPhone();

        // A payload that carries both is self-inconsistent. Refuse it rather
        // than picking a winner: the caller throws loud, which is the correct
        // outcome for evidence that disagrees with itself.
        if ($email !== null && $phone !== null) {
            return null;
        }

        if ($email !== null) {
            return $this->confirm($this->find($this->emailColumn, $email), $sessionUserId);
        }

        if ($phone !== null) {
            return $this->confirm($this->find($this->phoneColumn, $phone), $sessionUserId);
        }

        return null; // neither field present → unresolved → the service throws loud
    }

    /**
     * REFUSAL 2 — the matched row must BE the bound session's user.
     *
     * A claim may confirm the account the nonce already bound this login to;
     * it may never name a different one. A null/unusable key is a refusal too:
     * an unidentifiable row cannot be shown to be the bound user.
     */
    private function confirm(?Model $user, int $sessionUserId): ?Model
    {
        if (! $user instanceof Model) {
            return null;
        }

        $key = $user->getKey();

        if (! is_int($key) && ! (is_string($key) && $key !== '' && ltrim($key, '0123456789') === '')) {
            return null;
        }

        return (int) $key === $sessionUserId ? $user : null;
    }

    /**
     * One lookup against the host's user table, via the injected seam when
     * present and via a guarded Eloquent static otherwise.
     */
    private function find(string $column, string $value): ?Model
    {
        if ($this->finder !== null) {
            $user = ($this->finder)($column, $value);

            return $user instanceof Model ? $user : null;
        }

        if ($this->userClass === '' || ! class_exists($this->userClass) || ! is_subclass_of($this->userClass, Model::class)) {
            return null;
        }

        /** @var class-string<Model> $userClass */
        $userClass = $this->userClass;

        $user = $userClass::query()->where($column, $value)->first();

        return $user instanceof Model ? $user : null;
    }
}
