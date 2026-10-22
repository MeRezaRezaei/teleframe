<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

/**
 * PassportConfig — plain-PHP opt-in reader for the `teleframe.passport.*`
 * values (design §2/§7, IdentityConfig precedent). Constructible with a raw
 * config array (Laravel's `config('teleframe.passport')` or a plain array)
 * and defaults matching design §7 — INERT BY DEFAULT: enabled=false means
 * the module is fully constructible but nothing runs.
 *
 * Never touches a container or a facade; every read is a plain array
 * lookup. All coercion is string/int/bool typed — zero-regex.
 */
final class PassportConfig
{
    /**
     * The SecureValueTypes `requestPassport` can ask for as scope elements
     * (§8) — every type except email and phone, which are not scope types.
     *
     * @var list<string>
     */
    private const SCOPE_ELEMENTS = [
        'personal_details',
        // §6/the Bot API `EncryptedPassportElement.type` vocabulary also
        // contains the two IDENTITY element types, and they are the only way
        // to ASK for the verified email/phone. The reference's §8 list covers
        // the document types; the element-type vocabulary Telegram accepts in
        // a `scope` is the `EncryptedPassportElement.type` list, which
        // includes `email` and `phone_number` (see
        // `src/Schema/schema/sources/botapi-spec.json`). A scope that never
        // asks for them means Telegram never puts them in `PassportData`, and
        // the §6 channel this package reads stays empty.
        'email',
        'phone_number',
        'passport',
        'internal_passport',
        'driver_license',
        'identity_card',
        'address',
        'utility_bill',
        'bank_statement',
        'rental_agreement',
        'passport_registration',
        'temporary_registration',
    ];

    /** @var array<string, mixed> */
    private readonly array $config;

    /**
     * @param  array<string, mixed>  $config  the teleframe.passport values
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge(self::defaults(), $config);
    }

    /** Opt-in gate: false ⇒ module inert (constructible, nothing runs). */
    public function enabled(): bool
    {
        return (bool) $this->config['enabled'];
    }

    /** Bot RSA private key: a PATH to the PEM file (never the PEM itself). */
    public function privateKeyPath(): string
    {
        return (string) $this->config['private_key_path'];
    }

    /**
     * Tenant account id. Null when the config carries 0 (design: account_id
     * env 0 → fall back to the Identity primary-account rule).
     */
    public function primaryAccountId(): ?int
    {
        $accountId = (int) $this->config['account_id'];

        return $accountId === 0 ? null : $accountId;
    }

    /** Nonce registry TTL in seconds (issue/consume window). */
    public function nonceTtl(): int
    {
        return (int) $this->config['nonce_ttl'];
    }

    /** PSR-16 store key for the nonce registry ('' = module default key). */
    public function nonceStore(): string
    {
        return (string) $this->config['nonce_store'];
    }

    /**
     * Which SecureValueTypes the host accepts (allow-list; empty = refuse
     * all, loud at validate()).
     *
     * @return list<string>
     */
    public function allowedTypes(): array
    {
        $allowed = $this->config['allowed_types'] ?? null;
        if (! is_array($allowed)) {
            return self::defaults()['allowed_types'];
        }

        $types = [];
        foreach ($allowed as $type) {
            if (is_string($type) && $type !== '') {
                $types[] = $type;
            }
        }

        return $types;
    }

    /** Identity resolution mode: 'session' (default) | 'field' (wave 2). */
    public function userLookup(): string
    {
        return (string) $this->config['user_lookup'];
    }

    /** Optional SRP/2FA re-confirm gate (G5 — default off, seam only). */
    public function requirePasswordConfirmation(): bool
    {
        return (bool) $this->config['require_password_confirmation'];
    }

    /** Gated wire extension for the accept/import/password family (default off). */
    public function acceptFamily(): bool
    {
        return (bool) ($this->config['accept_family'] ?? false);
    }

    public function acceptFamilyEnabled(): bool { return $this->acceptFamily(); }
    public function acceptFamilyWireEnabled(): bool { return $this->acceptFamily(); }
    public function acceptFamilyWire(): bool { return $this->acceptFamily(); }

    /**
     * Whether the host DEMANDS that a claim-based identity be verified before
     * it is trusted (default false).
     *
     * This is the switch that was a promise rather than a check: the provider
     * only ever read it to refuse building a FieldIdentityResolver without it,
     * and nothing on the login path read it at all. It is now consulted by
     * `PassportLoginService` — see `identityRestsOnUnverifiedField()` for the
     * exact refusal condition.
     *
     * Two distinct meanings, deliberately kept apart:
     *  - TRUE  = "I require proof" → a login whose identity rests on a field
     *    Telegram did NOT put in its vetted `secure_data` list is REFUSED.
     *    The host has demanded verification, so an unverified claim is not
     *    good enough to log anyone in.
     *  - FALSE = the shipped default: field-mode stays the host's own
     *    responsibility, and session mode (the default lookup) is untouched
     *    because it binds to an already-authenticated session, not a claim.
     */
    public function requireVerified(): bool
    {
        return (bool) ($this->config['require_verified'] ?? false);
    }

    /** users-table column field mode compares a payload `email` against. */
    public function emailColumn(): string
    {
        $column = (string) ($this->config['email_column'] ?? '');

        return $column === '' ? 'email' : $column;
    }

    /**
     * May an ANONYMOUS visitor become a logged-in Laravel user through
     * Telegram Passport? (the shipped default is FALSE)
     *
     * This is a different capability from the module being `enabled`, and the
     * two are deliberately not the same switch:
     *
     *  - `enabled` = the ATTACH flow. It takes a user who is ALREADY
     *    authenticated and writes their Telegram identity onto them. Turning
     *    it on lets an existing account gain a linked Telegram account; it
     *    never authenticates anyone.
     *  - `anonymous_login` = LOGGING IN. It starts with no Laravel session
     *    and ends with one, so it can create the authenticated state that
     *    `enabled` only ever consumes. Turning it on means: a visitor with a
     *    Telegram-verified email or phone that already matches a row in your
     *    users table can obtain a Laravel session, without a password.
     *
     * So a host that only wants Telegram identity attached to accounts it
     * already manages leaves this false, and nothing about the existing flow
     * changes. A host that reads this and wants the capability sets it
     * deliberately — read `unknownUserPolicy()` in the same breath.
     *
     * @see PassportAnonymousLoginService for what the path actually enforces.
     */
    public function anonymousLoginEnabled(): bool
    {
        return (bool) ($this->config['anonymous_login'] ?? false);
    }

    /**
     * What happens when a Telegram-VERIFIED email/phone matches no user on the
     * anonymous login path. The only shipped value is `refuse`.
     *
     * Auto-provisioning is a real product decision and is NOT made here: it
     * decides whether any Telegram account holder can appear in the users
     * table, and what an unverified-looking-but-Telegram-vetted address
     * becomes as a login. Refusing keeps that choice with the host, and
     * keeping it as a CONFIG KEY rather than a branch in the code means the
     * decision is visible and reviewable instead of buried.
     */
    public function unknownUserPolicy(): string
    {
        $policy = (string) ($this->config['unknown_user'] ?? 'refuse');

        return $policy === '' ? 'refuse' : $policy;
    }

    /**
     * Requests per minute allowed on the ANONYMOUS login endpoints, keyed by
     * client IP. An unauthenticated authentication surface is a
     * credential-guessing surface, so it ships throttled rather than left to
     * the host to remember. 0 disables the package's own ceiling (the host's
     * global limiter still applies).
     */
    public function loginRateLimit(): int
    {
        $limit = (int) ($this->config['login_rate_limit'] ?? 10);

        return $limit < 0 ? 0 : $limit;
    }

    /**
     * Must the one-time nonce store be able to consume atomically?
     *
     * A plain PSR-16 store cannot: `get` then `delete` lets two overlapping
     * consumes both observe the nonce, and both log in. Sequential replays are
     * still refused either way, so the default is false — a loud bind-time
     * refusal would break every host on file or database cache. A host that
     * needs the hard guarantee sets this true and gets a NonAtomicNonceStore
     * Exception at resolve instead of a double login at runtime.
     */
    public function requireAtomicNonce(): bool
    {
        return (bool) ($this->config['require_atomic_nonce'] ?? false);
    }

    /** users-table column field mode compares a payload `phone` against. */
    public function phoneColumn(): string
    {
        $column = (string) ($this->config['phone_column'] ?? '');

        return $column === '' ? 'phone' : $column;
    }

    /**
     * The bot's own Telegram user id (0 = unconfigured) — the `bot_id`
     * `requestPassport` needs. Public identity, never a credential.
     */
    public function botId(): int
    {
        return (int) ($this->config['bot_id'] ?? 0);
    }

    /** The bot's @username WITHOUT the leading '@' ('' = unconfigured). */
    public function botUsername(): string
    {
        return ltrim((string) ($this->config['bot_username'] ?? ''), '@');
    }

    /**
     * The scope `requestPassport` asks for. Defaults to the same three
     * types as the allow-list; an empty configured list is honoured as
     * empty (Telegram accepts a bare personal-details request).
     *
     * @return list<string>
     */
    public function scope(): array
    {
        $scope = $this->config['scope'] ?? null;
        if (! is_array($scope)) {
            return self::defaults()['scope'];
        }

        $types = [];
        foreach ($scope as $type) {
            if (is_string($type) && $type !== '') {
                $types[] = $type;
            }
        }

        return $types;
    }

    /**
     * The `scope` argument of `requestPassport`, in the shape the Bot API
     * actually accepts: `{"v": 1, "data": [ … ]}` (§8). `v` MUST be 1.
     *
     * NOT the same value as `scope()`: this is the wire object, and it carries
     * only the entries that are SCOPE ELEMENTS. The shipped `scope` list mixes
     * two vocabularies — the SecureValueTypes a document request can name,
     * plus the host-facing `email` and `phone` — so the wire object has to
     * TRANSLATE the second pair into the element-type names the Bot API
     * actually accepts.
     *
     * `PassportScopeElementOne.type` is documented as "One of 'personal_details',
     * 'passport', 'driver_license', 'identity_card', 'internal_passport',
     * 'address', 'utility_bill', 'bank_statement', 'rental_agreement',
     * 'passport_registration', 'temporary_registration', '**phone_number**',
     * '**email**'", and the manual's Fields table lists `phone_number` and
     * `email` as requestable, with a plain `String` payload. So they ARE scope
     * types, and the ONE translation needed is the spelling: the Bot API calls
     * the phone `phone_number`, not `phone`. (An earlier revision of this
     * docblock claimed the opposite and dropped them from the scope on that
     * basis. It was wrong, and wrong in the dangerous direction: a scope that
     * never asks for the email and the phone means Telegram never puts them
     * in `PassportData.data`, so the §6 channel this package reads stays
     * empty and email/phone login cannot work at all. "They arrive as
     * plaintext" describes the RESPONSE, not whether they are requestable.)
     *
     * Unknown entries are DROPPED rather than forwarded, because one
     * spec-invalid element makes the whole `requestPassport` fail: a typo in a
     * host’s config would otherwise cost the login entirely. The §8 aliases
     * are expanded, and each type appears at most once.
     *
     * @return array{v: int, data: list<string>}
     */
    public function requestScope(): array
    {
        $data = [];

        foreach ($this->scope() as $type) {
            foreach (self::expandScopeAlias($type) as $expanded) {
                if (in_array($expanded, $data, true)) {
                    continue; // "each type may appear only once"
                }

                $data[] = $expanded;
            }
        }

        return ['v' => 1, 'data' => $data];
    }

    /**
     * §8 aliases → the concrete element types, plus the one NAME TRANSLATION
     * the vocabulary requires: the Bot API's element type for a phone is
     * `phone_number`, not `phone`, so a host that configured `phone` (the
     * shipped default spelling, and the name of the users-table column) still
     * asks Telegram for the right thing.
     *
     * The previous revision dropped `email` and `phone` from the scope
     * entirely, on the reading that they were "not scope types" because §6
     * says they arrive as plaintext. The plaintext is the RESPONSE shape; it
     * does not mean the field is unsolicited. The Bot API element-type
     * vocabulary (`EncryptedPassportElement.type`, vendored at
     * `src/Schema/schema/sources/botapi-spec.json`) ends with
     * `"phone_number", "email"`, and `PassportData` carries them per §6. Not
     * asking for them guarantees they never arrive.
     *
     * Zero-regex: array lookups and a `match`.
     *
     * @return list<string>
     */
    private static function expandScopeAlias(string $type): array
    {
        return match ($type) {
            'id_document' => ['passport', 'driver_license', 'identity_card', 'internal_passport'],
            'address_document' => ['utility_bill', 'bank_statement', 'rental_agreement'],
            'phone' => ['phone_number'],
            default => in_array($type, self::SCOPE_ELEMENTS, true) ? [$type] : [],
        };
    }

    /**
     * The bot RSA PUBLIC key (PEM) `requestPassport` carries. Public by
     * definition — the private half never leaves PassportKeyLoader.
     */
    public function publicKey(): string
    {
        return (string) ($this->config['public_key'] ?? '');
    }

    /**
     * Where the HTTP login surface sends the user after a successful
     * passport login ('' = stay on the response; the host decides).
     */
    public function successRedirect(): string
    {
        return (string) ($this->config['success_redirect'] ?? '');
    }

    /**
     * Design §7 defaults: inert, no key path, no tenant, 900s nonce TTL,
     * default nonce store key, the three passport identity types allowed,
     * session-based user lookup on email.
     *
     * @return array<string, mixed>
     */
    private static function defaults(): array
    {
        return [
            'enabled' => false,
            'private_key_path' => '',
            'account_id' => 0,
            'nonce_ttl' => 900,
            'nonce_store' => '',
            'allowed_types' => ['personal_details', 'email', 'phone'],
            'user_lookup' => 'session',
            'require_password_confirmation' => false,
            'accept_family' => false,
            // The three keys the Laravel provider reads but this class never
            // exposed: a plain-PHP host building `new PassportConfig([...])`
            // used to be unable to set or even see them, so the two
            // configuration paths could silently disagree. Defaults mirror
            // the provider's own fallbacks exactly (PassportServiceProvider
            // registerIdentity): require_verified=false, email, phone.
            'require_verified' => false,
            'require_atomic_nonce' => false,
            'email_column' => 'email',
            'phone_column' => 'phone',
            // The ANONYMOUS login capability, a deliberately SEPARATE switch
            // from `enabled`. Default FALSE: a host that configures Passport
            // to attach Telegram identities to accounts it already manages
            // must not thereby also hand sessions to visitors. See
            // anonymousLoginEnabled() for what turning it on means, and
            // unknownUserPolicy() for the decision it does NOT make.
            'anonymous_login' => false,
            'unknown_user' => 'refuse',
            'login_rate_limit' => 10,
            // The Telegram-side handshake the HTTP login surface hands to
            // the host: which bot asks, for what, and where the user lands
            // afterwards. All public values (bot identity + a public key) —
            // never a credential.
            'bot_id' => 0,
            'bot_username' => '',
            'scope' => ['personal_details', 'email', 'phone'],
            'public_key' => '',
            'success_redirect' => '',
        ];
    }
}
