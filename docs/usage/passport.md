# Usage — PASSPORT: end-to-end KYC and identity verification

> **Release area 5 of 5.** Telegram Passport is end-to-end encrypted: the user
> submits identity documents to Telegram, your bot receives the encrypted
> envelope, you decrypt with your RSA private key, validate, and attach the
> verified Telegram identity to a Laravel user. Crypto lives in
> `MeRezaRezaei\Teleframe\Core\Passport` (pure PHP); the orchestrator in
> `MeRezaRezaei\Teleframe\Passport`.
>
> **Two flows, one switch.** `PassportLoginService::login()` takes a
> non-nullable `sessionUserId` and refuses unless the consumed nonce's user id
> matches the presenting session, so on THAT path the user is ALWAYS already
> authenticated by your own guard. That is what `teleframe.passport.enabled`
> buys: a verified identity attached to an existing account.
>
> Logging an anonymous visitor IN is the other half, and it is a **separate
> capability that is OFF by default**: `PassportAnonymousLoginService` plus the
> `passport/login/{start,callback}` route pair, behind
> `teleframe.passport.anonymous_login`. It requires a Telegram-**verified**
> field, binds the trip to the browser session that started it, and refuses a
> field matching no user (`unknown_user=refuse`; the package never provisions
> an account). See [telegram-passport.md §3.3](../telegram-passport.md).

---

## 0. WHAT THIS PACKAGE DOES NOT DO — read before you rely on it

This is **identity-fields-only** Telegram Passport. It is not full Passport,
and nothing below should be read as a claim that it is.

**Not implemented: the document surface.** The MTProto `SecureValue` traversal
(`SecureValueTraversal`) now REACHES `front_side`, `reverse_side`, `selfie`,
`translation` and `files` and counts them, but it does not download them with
`getFile`, does not decrypt them and does not validate them anywhere in the
module. There is no modelling of `PersonalDetails`, `IdDocumentData` or
`ResidentialAddress`, and the intake path still never performs the nested
`SecureData.data` decryption, so a `secure_data` document entry arrives here
still encrypted. The nested CRYPTO primitive does exist and is
spec-conformant (`PassportDecryptor::decryptDataCredentials()` /
`decryptFileCredentials()`) — but nothing calls it with bytes, because
fetching them is the part that does not exist.

**Not implemented: error re-requesting.** There is no `setPassportDataErrors`
(Bot API) call and no `users.setSecureValueErrors` (MTProto) call.
`PassportFieldError` / `PassportAuthorizationForm` only *read* the per-field
errors Telegram returns in an authorization form. A host therefore cannot tell
Telegram that a field was rejected, and a user cannot be asked to correct and
resend.

**The verified email/phone channel — this was broken, and is now fixed;
read the remaining limits, not the old ones.** *(Corrected 2026-09-26. The
paragraph this replaces said the channel was "not implemented correctly" and
that email/phone-based login did not work against real Telegram data. That
was true until `af227af7` and `8dd68fa7`, and leaving it here would have been
a false record in the safe direction — a doc that under-promises a shipped
thing is still a doc that cannot be trusted.)*

§6 of the normative reference (`docs/reference/telegram-passport-official.md`)
is explicit that a verified email/phone is **not** inside the encrypted blob.
The code read it from three coordinates Telegram never writes —
`secure_data.email.address`, `secure_data.phone.phone_number`, and the
decrypted JSON's own top-level `email`/`phone` — and the dashboard controller
rebuilt `passport_data` with `credentials` only, discarding the values a real
Bot API update carries. Email/phone-based login could not work against real
Telegram data at all.

**Where the values actually are, and where they are read now:** per §5 the
`secure_data` keys are the 11 document types plus `personal_details`, and the
`Credentials` JSON is `{secure_data, nonce}`, so none of those three
coordinates exists. The verified values are **plaintext on the
`EncryptedPassportElement` entries in `PassportData.data[]`** — an element
typed `email` carries `email`, one typed `phone_number` carries
`phone_number`, beside that element's own encrypted `data`. `PassportData` has
exactly two fields (`data`, `credentials`) and NO top-level
`email`/`phone_number`. `PassportVerifiedClaims` now reads that coordinate, and
the controller rebuilds a real `PassportData` with both fields. The test
fixtures are built as real `PassportData` objects, `data` array and all,
pinned from the vendored Bot API type (`src/Schema/schema/sources/botapi-spec.json`).

**What is still true, and is the honest limit of this module:**
- **Every Passport fixture is spec-derived.** Telegram is not reachable from
  the test environment, so the suites build their inputs from the vendored
  type. That defends against our own misreading — an earlier revision was
  green only because its fixtures were built in an invented shape, and the
  code and the suite agreed while both were unreachable from real Telegram —
  but it is not evidence against Telegram's.
- **Nothing has been run against the live Telegram API.** There is no live
  Passport gate, opt-in or otherwise. The 133-test suite and the
  spec-derivation are the whole of the evidence.
- **MTProto claim-based login works on the claim, and is still unproven at the
  edges.** *(Corrected 2026-09-27. The bullet this replaces said it "still does
  not work" and that the `values: Vector<SecureValue>` traversal was ABSENT.
  That was true until `068a8829`, and leaving it would have understated a
  shipped thing — the same failure in the opposite direction.)* The verified
  values on an MTProto-shaped update live at `secureValue.plain_data` as
  `securePlainEmail` / `securePlainPhone`, on the `values: Vector<SecureValue>`
  sibling of the `SecureCredentialsEncrypted`. `SecureValueTraversal::fromValues()`
  walks that vector and `PassportVerifiedClaims::fromSecureValues()` reads
  those leaves, returning the SAME value object the Bot API path returns — so a
  field-mode or anonymous login on an MTProto-shaped response resolves exactly
  as it does on a Bot API one. The two refusals survived the change: a leaf is
  a claim only under §7's type table (`secureValueTypeEmail` /
  `secureValueTypePhone`, from a `securePlainEmail` / `securePlainPhone` only),
  and two leaves that disagree yield NO claim. A shape with no claim still
  yields `none()` and a field-mode login on it is still REFUSED loudly.
  The login chain reads the sender from `message.from_id` as a `peerUser`
  Peer — the field a real `updateNewMessage` carries — and a `peerChat` /
  `peerChannel` / unnameable peer refuses loudly rather than guessing.
  **The limits that remain:** every fixture in these suites is spec-derived, so
  none of this is evidence about what Telegram actually sends, and **no live
  run exists**. Two agreement rules are worth knowing: the type gate FAILS
  CLOSED (a constructor this class cannot name is refused, never treated as
  permission), and email agreement compares domain case-folded with one trailing
  dot stripped while returning the first raw value byte-for-byte — because
  byte-exact comparison was denial-enabling, letting anyone who could append a
  `SecureValue` unlogin a victim by respelling their domain.

So: session-mode attach and email/phone login on BOTH Telegram-verified shapes
are implemented and spec-conformant; none of it is proven against live
Telegram; a shape that carries no §6 claim is refused on purpose, and is not
a bug report waiting to be filed.

`teleframe.passport.scope` mixes two vocabularies (document types plus
`email`/`phone`); the `requestPassport` argument this package hands you
(`PassportConfig::requestScope()`) emits only real scope elements in the
spec's `{"v":1,"data":[…]}` object. Use that one, not the flat list.

## 1. Setup (the part the docs can't do for you)

1. Generate an RSA-2048 keypair:
   ```bash
   openssl genrsa -out storage/app/keys/passport_private.pem 2048
   openssl rsa -in storage/app/keys/passport_private.pem -pubout -out storage/app/keys/passport_public.pem
   ```
2. Register `passport_public.pem` in **@BotFather** (`/mybots` → your bot →
   Bot Settings → Telegram Passport).
3. Point config at the private key with a **PATH, never the PEM text**:
   ```env
   TELEFRAME_PASSPORT_ENABLED=true
   TELEFRAME_PASSPORT_PRIVATE_KEY=/abs/path/to/passport_private.pem
   ```
   The key file must be 0600 (`PassportKeyLoader` checks); the key is loaded
   once and cached in memory.
4. Configure the tenant + nonce + identity seams
   (`TELEFRAME_PASSPORT_ACCOUNT_ID`, `TELEFRAME_PASSPORT_NONCE_TTL`,
   `TELEFRAME_PASSPORT_NONCE_STORE`, `TELEFRAME_PASSPORT_USER_LOOKUP`) — see
   §5.

**Inert by default.** `teleframe.passport.enabled=false` means the module is
fully constructible (all seams resolve to memory/fake defaults) but nothing
runs: `login()` throws loud **only when called**, never silent-drop.

## 2. Intake — `EncryptedCredentials`

The credentials arrive as three base64 strings. One DTO shape feeds the whole
pipeline (`data` + `hash` + `secret`, non-empty strings):

```php
use MeRezaRezaei\Teleframe\Passport\EncryptedCredentials;

// Bot API update: message.passport_data.credentials {data, hash, secret}
// MTProto update: SecureCredentialsEncrypted array {data, hash, secret}
$creds = EncryptedCredentials::fromUpdate($update);   // ?self — null on non-passport updates

// Loud path for already-extracted arrays:
$creds = EncryptedCredentials::fromArray($credentials); // throws PassportLoginException

$creds->data; $creds->hash; $creds->secret;              // readonly strings
$back = $creds->toArray();                               // ['data'=>…, 'hash'=>…, 'secret'=>…]
```

Intake rules: `fromUpdate()` is **quiet** on malformed input (missing/empty
field → null — the webhook must not throw on non-passport updates) but
**loud** when well-formed fields exceed the per-field size caps (data 65536 /
secret 8192 / hash 64 bytes) — unbounded attacker input is refused at intake,
never buffered downstream. `fromArray()` is loud on both.

## 3. Decrypt — `PassportDecryptor`

```php
use MeRezaRezaei\Teleframe\Core\Passport\PassportDecryptor;

$decrypted = PassportDecryptor::decryptCredentials(
    encryptedData:   $creds->data,     // base64
    encryptedSecret: $creds->secret,   // base64 — sealed with your RSA public key
    privateKeyPem:   $pem,             // your private key, PEM string
    hash:            $creds->hash,     // base64 SHA-256 of the plaintext
); // array<string, mixed> — the decrypted JSON (e.g. ['personal_details' => […]] )
```

The five steps, exactly as shipped:

1. Base64-decode `data` + `secret` — a decode failure throws
   `RuntimeException('Invalid base64 passport payload.')`.
2. RSA-OAEP-decrypt the secret with the private key (**OAEP only** — the old
   PKCS#1 v1.5 fallback is gone; it was a padding-mode oracle + downgrade).
3. The hash must strict-decode to **exactly 32 raw bytes** (a SHA-256).
4. Derive AES key/IV: `sha512(secret . rawHash)` → key `[0..32]`,
   iv `[32..48]`; decrypt `aes-256-cbc`.
5. Verify integrity **constant-time** (`hash_equals`): `sha256(plaintext)`
   must equal the raw hash; then JSON-decode.

One failure message for every failure path after step 1 —
`PassportDecryptor::DECRYPT_ERROR = 'Failed to decrypt Passport credentials.'`.
RSA-OAEP failure, AES-padding failure and hash mismatch are **indistinguishable
on purpose** (padding-oracle hardening) — an attacker probing the endpoint
never learns which step failed.

## 4. Identity orchestrator — `PassportLoginService`

Facade `MeRezaRezaei\Teleframe\Laravel\Facades\Passport` (accessor
`teleclient.passport.service`; compose facade `Teleframe::passport()` resolves
the same instance):

```php
use MeRezaRezaei\Teleframe\Laravel\Facades\Passport;

// In your passport webhook controller. The session id is REQUIRED and
// non-nullable: the nonce round-trips through Telegram (URLs, referrer logs,
// proxy logs), so it proves the payload is intact and not who is presenting
// it, and the check that binds the two cannot be skipped. A host with no
// authenticated session must not call this.
$login = Passport::login($update, sessionUserId: Auth::id());  // LoginResult — throws on failure
$login->tgUserId;         // int — the Telegram user who submitted
$login->accountId;        // ?int — the tenant account
$login->user;             // Model — the resolved Laravel user
$login->binding;          // mixed — the identity binding row
$login->fieldsVerified;   // array — which SecureValueTypes verified

// Pipe-safe variant: null when the update carries no passport data. With
// credentials in it and no session id, it is REFUSED LOUD, never routed.
$maybe = Passport::handleUpdate($update, Auth::id());   // ?LoginResult

// Issue a one-time nonce for a web session (must be present in the update):
$nonce = Passport::issueNonce($userId);     // string

$service = Passport::service();             // PassportLoginService
```

**Flow** (orchestrator): `EncryptedCredentials::fromUpdate` → `PassportDecryptor`
→ nonce verify (`PassportPayload::nonce()` must match an issued, un-consumed
nonce) → identity resolution → binding write → `onLogin` host hook. Allowed
field types are an allow-list (`PassportConfig::allowedTypes()`).

`PassportPayload` (the decrypted envelope) — `nonce(): string`,
`hasType(string): bool`, `plainValue(string): ?string` (e.g.
`plainValue('email')`), `toArray(): array`.

**Nonces** — `NonceRegistry` interface: `issue(int $userId): string`,
`consume(string $nonce): ?int` (returns the user id, `null` on unknown/reused).
Shipped implementations: `MemoryNonceRegistry` (in-process; what `''`
`nonce_store` binds) and `CacheNonceRegistry` over the host's PSR-16 cache
(durable — nonces survive between the issuing web session and the webhook;
store keys are `$keyPrefix . $nonce`, consumed = get→delete→return, with the
documented TOCTOU residual race on shared PSR-16 stores).

### `teleframe:passport`

```bash
php artisan teleframe:passport status        # config + seams (all resolved?)
php artisan teleframe:passport nonce --user=7 # issue a probe nonce (and consume it)
php artisan teleframe:passport flush          # clear the nonce registry
```

## 5. Config (`teleframe.passport.*`, env `TELEFRAME_PASSPORT_*`)

| Key | Default | Meaning |
| --- | --- | --- |
| `enabled` | `false` | master switch; false = constructible, `login()` throws loud only when called |
| `private_key_path` (env `TELEFRAME_PASSPORT_PRIVATE_KEY`) | `''` | **PATH to the PEM** (0600-checked, cached); never the PEM itself |
| `account_id` (env `TELEFRAME_PASSPORT_ACCOUNT_ID`) | `0` | tenant; 0 falls back to the Identity primary-account rule (`primaryAccountId()` → null) |
| `nonce_ttl` (env `TELEFRAME_PASSPORT_NONCE_TTL`) | `900` | seconds a nonce is valid |
| `nonce_store` (env `TELEFRAME_PASSPORT_NONCE_STORE`) | `''` | `''` = MemoryNonceRegistry; a PSR-16 key = CacheNonceRegistry |
| `allowed_types` | `['personal_details','email','phone']` | SecureValueTypes the host accepts (empty = refuse all, loud) |
| `user_lookup` / `user_lookup_field` | `'session'` / `'email'` | `'session'` (nonce userId) \| `'field'` (wave 2: email/phone lookup) |
| `require_password_confirmation` | `false` | optional SRP/2FA re-confirm via the callable chain (G5) |
| `anonymous_login` (env `TELEFRAME_PASSPORT_ANONYMOUS_LOGIN`) | `false` | **THE LOGIN CAPABILITY**, a separate switch from `enabled`. True = the `passport/login/*` pair may turn a Telegram-verified field into a Laravel session. Off means those routes answer 403. |
| `unknown_user` (env `TELEFRAME_PASSPORT_UNKNOWN_USER`) | `'refuse'` | What a verified field matching no user does. `refuse` is the ONLY shipped value. A verified field matching no user is refused loudly and the package **never creates a row** — no value of this key provisions. |
| `login_rate_limit` (env `TELEFRAME_PASSPORT_LOGIN_RATE_LIMIT`) | `10` | Requests per minute per IP on BOTH anonymous routes (marker minting included). `0` removes the package's ceiling; your global limiter still applies. |

## 6. Security posture (shipped, do not weaken)

- **One decrypt error message** → no padding oracle (§3).
- **Size caps at intake** (§2) → no unbounded buffering.
- **Key path, not key text**, in config; 0600 check; in-memory cache only.
- **Nonce = one-time + TTL** → replay of a consumed webhook fails.
- **Inert by default, loud on use** — an unconfigured host never silently
  drops a passport webhook.
- End-to-end test witness: `tests/Core/PassportDecryptorTest.php`
  (RSA-2048 → OAEP secret → `sha512(secret . rawHash)` KDF → AES-256-CBC →
  `decryptCredentials`) and `tests/Passport/PassportDecryptorE2eTest.php`.

---

Related: [notify.md](notify.md) (client acquisition feeding the webhook) ·
[app-framework.md](app-framework.md) (the update pipeline where
`handleUpdate` plugs in) · [telegram-passport.md](../telegram-passport.md)
(the original decrypt-in-webhook guide).