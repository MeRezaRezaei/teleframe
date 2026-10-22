<?php

/*
|--------------------------------------------------------------------------
| Teleframe Passport — Laravel login via Telegram Passport (design §7)
|--------------------------------------------------------------------------
| Merged into config under the `teleframe.passport` key by
| PassportServiceProvider (this package's OWN provider — registered via
| composer.json extra.laravel.providers, deliberately NOT hooked into
| TeleframeServiceProvider). Hosts may publish and override any key.
|
| Credentials ruling (AGENTS.md hard rule): this file carries a PATH to the
| bot RSA private key PEM — never the PEM itself. PassportKeyLoader resolves
| the path → key string on first use, 0600-checked, in-memory cached; the
| webhook update arrays and any derived key material live only in-call.
|
| Runtime posture: INERT BY DEFAULT — "when one configures them". enabled=
| false means the module is fully constructible (all seams resolve to
| memory/fake defaults) but nothing runs; login() throws loud only when
| someone actually calls it, never silent-drop.
*/

return [

    /* INERT BY DEFAULT (G2). False ⇒ the module is fully constructible but
       nothing runs: login() throws loud only when called. */
    'enabled' => (bool) env('TELEFRAME_PASSPORT_ENABLED', false),

    /* Bot RSA private key: a PATH to the PEM file (0600-checked by
       PassportKeyLoader), never the PEM itself. */
    'private_key_path' => (string) env('TELEFRAME_PASSPORT_PRIVATE_KEY', ''),

    /* Tenant: explicit account id; 0 falls back to the Identity
       primary-account rule (Q7) — PassportConfig::primaryAccountId() nulls
       it. */
    'account_id' => (int) env('TELEFRAME_PASSPORT_ACCOUNT_ID', 0),

    /* Nonce registry: the issue/consume TTL (seconds) and the PSR-16 store
       key. '' binds the in-process MemoryNonceRegistry (tests / plain-PHP);
       a store key binds CacheNonceRegistry over the host's PSR-16 cache —
       durable, so nonces survive between the issuing web session and the
       webhook. */
    'nonce_ttl' => (int) env('TELEFRAME_PASSPORT_NONCE_TTL', 900),
    'nonce_store' => (string) env('TELEFRAME_PASSPORT_NONCE_STORE', ''),

    /*
    | Refuse to boot unless the nonce store can consume ATOMICALLY.
    |
    | A plain PSR-16 store (file, database, array) consumes with get-then-delete.
    | Sequential replays are refused either way; two OVERLAPPING consumes — a
    | redelivered webhook, a double submit — can both observe the nonce and both
    | log in. A Redis-backed store is wrapped in a single server-side Lua
    | GET+DEL and is genuinely atomic.
    |
    | Default false so a file/database-cache host keeps working unchanged. Set
    | true and the provider throws NonAtomicNonceStoreException at resolve,
    | naming the store class — a failed boot beats a double login.
    */
    'require_atomic_nonce' => (bool) env('TELEFRAME_PASSPORT_REQUIRE_ATOMIC_NONCE', false),

    /* Which SecureValueTypes the host accepts (allow-list; empty = refuse
       all, loud at validate()).

       READ THIS BEFORE TRUSTING 'email'/'phone' HERE. They are not
       SecureValueTypes: per the normative reference §5, the `secure_data`
       keys are the 11 document types plus `personal_details` and nothing
       else, and a payload carrying a `secure_data.email` / `secure_data.phone`
       is REFUSED outright by the validator, whatever this list says.

       They are kept here anyway, and deliberately so: `email` / `phone` are
       this package's HOST-FACING names for the two verified identity claims —
       they are the users-table column names the field resolver looks the value
       up under, and they are the type names recorded in
       `LoginResult::fieldsVerified` and the provenance rows. Dropping them
       from the shipped default would silently change what a host's login
       records say. The claim VALUE comes from the Telegram-verified §6
       location — the plaintext `email` / `phone_number` field of the matching
       `EncryptedPassportElement` in `PassportData.data` (Bot API) — never
       from a `secure_data` key. */
    'allowed_types' => ['personal_details', 'email', 'phone'],

    /* Identity resolution: 'session' (default — the nonce-issued userId) |
       'field' (wave 2 — email/phone lookup). */
    'user_lookup' => (string) env('TELEFRAME_PASSPORT_USER_LOOKUP', 'session'),

    /* Which claim 'field' mode binds to is INFERRED, not configured: the
       resolver uses whichever the decrypted payload carries — `email` against
       email_column, `phone` against phone_column — and refuses a payload
       carrying both, because evidence that disagrees with itself is not
       evidence. There is deliberately no user_lookup_field key: it was read and
       discarded, so setting it changed nothing and said nothing. */


    /*
    | Field mode binds a login to a CLAIM inside the decrypted payload (an
    | email or phone), not to a web session. That is only safe if the HOST
    | verified that claim itself. This flag is the acknowledgement, and
    | user_lookup=field refuses to resolve without it.
    */
    'require_verified' => (bool) env('TELEFRAME_PASSPORT_REQUIRE_VERIFIED', false),

    /* Users-table columns compared against the payload's plain values. */
    'email_column' => (string) env('TELEFRAME_PASSPORT_EMAIL_COLUMN', 'email'),
    'phone_column' => (string) env('TELEFRAME_PASSPORT_PHONE_COLUMN', 'phone'),

    /* Optional SRP/2FA re-confirm via the callable chain (G5). Off = skip. */
    'require_password_confirmation' => (bool) env('TELEFRAME_PASSPORT_REQUIRE_PASSWORD', false),

    /*
    | Gated wire extension for the accept/import/password family. These methods
    | are 229-catalog keys that the 227 wire does not encode by default; the
    | extension surfaces them ONLY when explicitly enabled. Default OFF — the
    | intentional 227-vs-229 gap stays intact unless a host opts in.
    */
    'accept_family' => (bool) env('TELEFRAME_PASSPORT_ACCEPT_FAMILY', false),

    /*
    | The Telegram-side handshake the HTTP login surface hands back from
    | `GET/POST /teleframe/passport/start` (see PassportController): the
    | bot that asks, the scope it asks for, and the public key it signs
    | with. NONE of these is a credential — the bot identity and the RSA
    | PUBLIC key are public; the private half never leaves PassportKeyLoader.
    |
    | With bot_username set, `start` also returns a ready-to-open
    | https://t.me/<bot>?start=<nonce> deep link the host can drop on a
    | login page, so no glue code is needed to start the journey.
    */
    'bot_id' => (int) env('TELEFRAME_PASSPORT_BOT_ID', 0),
    'bot_username' => (string) env('TELEFRAME_PASSPORT_BOT_USERNAME', ''),

    /* The TYPES a request may name. `email`/`phone` are not scope types —
       Telegram shares those from the user's Passport settings and never as
       a PassportScopeElement — so `PassportConfig::requestScope()` (the
       `requestPassport` argument this package hands you) drops them and emits
       the spec's `{"v":1,"data":[…]}`. Add real element types here:
       personal_details, passport, internal_passport, driver_license,
       identity_card, address, utility_bill, bank_statement,
       rental_agreement, passport_registration, temporary_registration — or
       the two aliases `id_document` / `address_document`. */
    'scope' => ['personal_details', 'email', 'phone'],
    'public_key' => (string) env('TELEFRAME_PASSPORT_PUBLIC_KEY', ''),

    /* Where the callback sends the user after a successful login. '' = the
       callback answers JSON/HTML and the host decides. */
    'success_redirect' => (string) env('TELEFRAME_PASSPORT_SUCCESS_REDIRECT', ''),

    /*
    |--------------------------------------------------------------------------
    | ANONYMOUS LOGIN — logging a visitor IN (a DIFFERENT capability)
    |--------------------------------------------------------------------------
    |
    | READ THIS BEFORE SETTING IT. `enabled` above is the ATTACH flow: it takes
    | a user who is ALREADY authenticated and writes their Telegram identity
    | onto them. It never authenticates anyone — every route behind it sits
    | inside the host's `auth` guard, and the callback refuses without a
    | session. It answers "which of my users linked a Telegram account".
    |
    | `anonymous_login` is the other half, and the one that makes Passport a
    | LOGIN system. It starts with no Laravel session and ends with one, so it
    | can create the authenticated state `enabled` only ever consumes. Turning
    | it on means, concretely:
    |
    |   A visitor who completes Telegram Passport with an email or phone that
    |   TELEGRAM VERIFIED, and that value already matches a row in your users
    |   table, obtains a Laravel session — no password, no invite, no admin.
    |   That row's email column is now a passwordless login credential.
    |
    | What it will NOT do, by design:
    |   * it never acts on a field Telegram did not list in its vetted
    |     `secure_data` — an unverified claim is refused outright, and this is
    |     unconditional here (not gated on `require_verified` below), because
    |     the field is the ENTIRE identity on this path;
    |   * it never creates an account — see `unknown_user` below;
    |   * it never replaces a live session — a browser that is already
    |     authenticated is refused and told to use the attach flow;
    |   * it never writes a session id, a nonce, a flow token or a decrypted
    |     field into a log, a response, a redirect or a URL.
    |
    | It IS bound to the browser that started the trip: a pending marker, minted
    | server-side before any Telegram traffic, single-use and expiring, records
    | a fingerprint of the starting session and a digest of that trip's nonce.
    | The callback must present the marker from that same browser and with
    | credentials minted for that same nonce. A leaked flow token therefore
    | logs nobody in.
    |
    | DEFAULT false. A host that only wants Telegram identities attached to
    | accounts it already manages never sets it, and nothing about the shipped
    | attach flow changes. Turning it on is a security decision, so make it
    | deliberately.
    */
    'anonymous_login' => (bool) env('TELEFRAME_PASSPORT_ANONYMOUS_LOGIN', false),

    /*
    | What an anonymous login does when the VERIFIED field matches no user.
    |
    | `refuse` is the only shipped value, and it is the safe one: the login is
    | refused loudly and NO row is written. Auto-provisioning is a real product
    | decision — it decides whether any Telegram account holder can appear in
    | your users table, and what a Telegram-vetted-but-otherwise-unconfirmed
    | address becomes as a login credential — so it is yours, not this
    | package's. Provision the user yourself and let them log in again.
    |
    | Kept as a config key rather than a branch in the code so the decision is
    | visible and reviewable instead of buried.
    */
    'unknown_user' => (string) env('TELEFRAME_PASSPORT_UNKNOWN_USER', 'refuse'),

    /*
    | Requests per minute on the ANONYMOUS login endpoints, keyed by client IP.
    |
    | An unauthenticated authentication endpoint is a credential-guessing
    | surface, so it ships throttled rather than left to the host to remember.
    | Both anonymous routes (start and callback) carry the same ceiling, which
    | bounds marker minting as well as credential presentation. Your own global
    | limiter still applies on top. 0 removes this ceiling without removing
    | yours.
    */
    'login_rate_limit' => (int) env('TELEFRAME_PASSPORT_LOGIN_RATE_LIMIT', 10),
];
