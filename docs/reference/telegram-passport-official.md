# Telegram Passport — normative reference

Upstream sources, fetched 2026-09-26 and vendored here so the implementation is
built against the **specification** rather than against anyone's inference:

- **Passport Manual (Bot API):** https://core.telegram.org/passport
- **Telegram Passport (MTProto):** https://core.telegram.org/api/passport
- Encryption internals: https://core.telegram.org/passport/encryption

## 0. Corrections to this summary (read before §3 and §6)

This file is a summary, and the upstream pages are authoritative. Three
statements in it were wrong and are corrected below; all are marked. Two
(C1, C2) were found by an audit; the third (C3) was found by implementing
against this file and finding the implementation unreachable from real
Telegram — which is the failure mode this section exists to prevent.

**C1 — §3.3/§4: the `hash` is over the AES INPUT, not over the ciphertext.**
This document previously said the integrity hash is `SHA256(ciphertext)`. That
is a misreading of the quote it was citing. Upstream writes, in this order:

> Credentials are padded to a length which is divisible by 16 bytes. To achieve
> this, 32 to 255 bytes are added at the beginning, where the first byte always
> holds the number of added bytes and the rest are random.
>
> A hash of the **padded credentials** credentials_hash is calculated:
> credentials_hash = SHA256( credentials )

The padding paragraph comes first, the hash is explicitly "a hash of the
padded credentials", and the variable that holds the ciphertext is
`encrypted_credentials` — `credentials` is the AES input. The same wording
appears for the nested elements ("We calculate the hash from this data
data_hash: data_hash = SHA256( data_bytes )", with
`encrypted_data = AES256_CBC_ENC(data, data_key, iv)`).

It also cannot be otherwise: the key is derived from this hash
(`data_secret_hash = SHA512(secret + hash)`), so "hash = SHA256(ciphertext)"
asks for a fixed point — a ciphertext whose own SHA-256 seeds the key that
produces it. No client can compute one and **no test fixture can be
constructed**, which is how the misreading can be settled rather than argued.
`PassportSpecConformanceTest` asserts both halves: the hash equals
SHA-256(padded plaintext) and does NOT equal SHA-256(ciphertext).

**C2 — §12's "no implicit padding" line was checked when the code did the
opposite.** AES-256-CBC here carries no PKCS#7 block: upstream pads the
payload itself to a multiple of 16. OpenSSL's default `OPENSSL_RAW_DATA`
mode still validates and strips PKCS#7, so it removes 16 bytes that were
never encrypted and fails outright on the random tail. The decryption must
carry `OPENSSL_ZERO_PADDING`.

Telegram Passport is a unified authorization method: a user uploads documents
once, then shares them with any service that asks. It is best modelled as an
**SSO / OpenID-Connect-shaped** federation — the Telegram client is the
identity provider, this service is the relying party, and `nonce` is the
correlation handle. What follows is only what an implementation must *do*; the
upstream pages are authoritative where this summary differs.

## 1. Roles and flow

1. The service requests the data it needs (`scope`, `public_key`, `nonce`).
2. The user accepts a privacy policy and agrees to share.
3. The Telegram app fetches the data from E2E-encrypted storage, encrypts it to
   the service's public key, and sends it.
4. The service decrypts, **verifies integrity**, checks for errors, re-requests
   anything missing, and only then signs the user up.

As a bot via the Bot API, the result arrives as an `Update` with
`passport_data`; all base64 fields must be decoded before use. Via MTProto it
arrives as `updateNewMessage` → `messageService` →
`messageActionSecureValuesSentMe`, whose `credentials` is a
`SecureCredentialsEncrypted` (data/hash/secret) and whose `values` is a
`Vector<SecureValue>`. MTProto fields are **raw binary**, not base64.

## 2. `nonce` — the SSO correlation handle

- Bot-specified. **"For security purposes it should be a cryptographically secure
  unique identifier… generated using a cryptographically secure pseudorandom
  number generator. You should never accept credentials with the same nonce
  twice."**
- After decryption, `Credentials.nonce` **must equal** the nonce sent in the
  request. Telegram states this as an `IMPORTANT`.
- So: CSPRNG, high entropy, single-use, and compared in constant time.

## 3. Credentials decryption — the exact algorithm

Given `EncryptedCredentials { data, hash, secret }` (base64 in Bot API, raw in
MTProto):

1. Decrypt `secret` with the service's RSA **private** key, **OAEP padding**
   (`OPENSSL_PKCS1_OAEP_PADDING` in PHP).
   `secret` is the only part encrypted to the service key.
2. ```
   credentials_secret_hash = SHA512( credentials_secret + credentials_hash )
   credentials_key = slice( credentials_secret_hash, 0, 32 )
   credentials_iv  = slice( credentials_secret_hash, 32, 16 )
   ```
   Concatenation, then raw (non-hex) SHA-512.
3. AES-256-CBC decrypt the `data` field with `credentials_key`/`credentials_iv`,
   **with no implicit block padding** (the payload is already a multiple of 16
   — see C2). Then verify `hash == SHA256( credentials_data )` over the
   decrypted, STILL-PADDED payload, i.e. the AES **input** — not over the
   ciphertext (see C1).
4. **The result is padded.** "Credentials data is padded with 32 to 255 random
   padding bytes to make its length divisible by 16 bytes. The first byte
   contains the length of this padding (including this byte). Remove the padding
   to get the data."

All hashes are raw binary, not hexits.

## 4. Nested decryption — same shape, twice more

**`DataCredentials { data_hash, secret }`** — for a `SecureValue.data` field:

```
data_secret_hash = SHA512( data_secret + data_hash )
data_key = slice( data_secret_hash, 0, 32 )
data_iv  = slice( data_secret_hash, 32, 16 )
```

AES-256-CBC decrypt the `EncryptedPassportElement.data` field (no implicit
block padding). **Verify `data_hash == SHA256( data )` over the decrypted,
still-padded payload — the AES input, not the ciphertext (C1).** Same 32–255
byte padding, first byte = padding length including itself. The plaintext is
one of `PersonalDetails`, `IdDocumentData` or `ResidentialAddress`.

**`FileCredentials { file_hash, secret }`** — for `front_side`, `reverse_side`,
`selfie`, `translation`, `files`:

```
file_secret_hash = SHA512( file_secret + file_hash )
file_key = slice( file_secret_hash, 0, 32 )
file_iv  = slice( file_secret_hash, 32, 16 )
```

Download the encrypted file with `getFile`, AES-256-CBC decrypt it (no
implicit block padding), **verify `file_hash == SHA256( file_content )` over
the decrypted, still-padded payload — the AES input (C1)** — then strip the
same padding. Files are JPG, max 10 MB.

## 5. `Credentials` JSON

```
{ "secure_data": { … }, "nonce": "…" }
```

`secure_data` keys are optional and depend on what was requested:
`personal_details`, `passport`, `internal_passport`, `driver_license`,
`identity_card`, `address`, `utility_bill`, `bank_statement`,
`rental_agreement`, `passport_registration`, `temporary_registration`.

## 6. Where email and phone actually arrive

This is the detail implementations most often get wrong — and it is where this
document was wrong once, so read **C3** below before relying on it.

- **Bot API:** they are returned as **plaintext strings on the
  `EncryptedPassportElement`** — the entries in `PassportData.data[]`. An
  element whose `type` is `"email"` carries the address in its own `email`
  field; one whose `type` is `"phone_number"` carries it in `phone_number`.
  They are *outside* the encrypted blob (they sit beside that element's
  `data`/`hash`), and the Bot API says so in as many words: *"User's verified
  email address; available only for `email` type"*.
- **MTProto:** they arrive in `secureValue.plain_data` as
  `securePlainEmail` / `securePlainPhone`.

Both are **already verified** by Telegram ("Emails and phone numbers sent using
telegram passport are *already verified*"). Reading a verified email out of an
arbitrary payload field instead of the vetted location is a security defect, not
a shortcut.

**C3 — §6 previously said the Bot API values are "top-level siblings of
`credentials`". They are not.** `PassportData` has exactly TWO fields:

```
PassportData
  data        Array of EncryptedPassportElement
  credentials EncryptedCredentials
```

There is no top-level `email` and no top-level `phone_number` on
`PassportData`; those two names are fields of an ELEMENT. A reader that looks
for them beside `credentials` finds nothing on a genuine response — which is
the same class of defect as the `secure_data.email` this very section exists to
warn about. That mis-reading was implemented (twice, in two successive
revisions) and was caught only because a test was written to pin the invented
coordinate, which is the strongest argument for pinning coordinates in tests
rather than inferring them from prose.

Also note the scope side, because it is the other half of the same trap: the
manual's `PassportScopeElementOne.type` DOES include `"phone_number"` and
`"email"`, and the Fields table lists them as requestable with a plain `String`
payload. They are therefore legitimately IN a `scope`. "They arrive as
plaintext" describes the RESPONSE shape and says nothing about whether they are
requestable. A scope that omits them means Telegram never puts them in
`PassportData.data`, so the §6 channel stays empty.


## 7. `SecureValue` fields

`data` (`SecureData`), `front_side`, `reverse_side`, `selfie`, `translation`
(`Vector<SecureFile>`), `files` (`Vector<SecureFile>`), `plain_data`
(`SecurePlainData`), `hash`.

Type → allowed fields:

| Type | Allowed |
| --- | --- |
| `secureValueTypeEmail`, `secureValueTypePhone` | `plain_data` |
| `secureValueTypePersonalDetails` | `data` |
| `secureValueTypePassport`, `secureValueTypeInternalPassport` | `data`, `front_side`, `selfie`, `translation` |
| `secureValueTypeDriverLicense`, `secureValueTypeIdentityCard` | `data`, `front_side`, `reverse_side`, `selfie`, `translation` |
| `secureValueTypeAddress` | `data` |
| `secureValueTypeUtilityBill`, `BankStatement`, `RentalAgreement`, `PassportRegistration`, `TemporaryRegistration` | `files`, `translation` |

## 8. Request scope

`scope` is `{"v": 1, "data": [...]}`; `v` **must be 1**. Each type may appear
only once across the whole array. An element is either one particular document
(`PassportScopeElementOne`, plus optional `selfie`, `translation`,
`native_names`) or `one_of` several (`PassportScopeElementOneOfSeveral`, which
must be several of the ID documents **or** several of the proof-of-address
documents, and takes `selfie`/`translation`).

Aliases: `id_document` ≡ one of `passport|driver_license|identity_card|internal_passport`;
`address_document` ≡ one of `utility_bill|bank_statement|rental_agreement`.

Telegram advises requesting `translation` **only after** a valid document has
been received.

Deep-link form (client apps):
`tg://passport?bot_id=…&scope=…&public_key=…&nonce=…`, or
`tg://resolve?domain=telegrampassport&…`. `callback_url` is optional and
supported by some clients. `payload` is **deprecated** — Passport 1.0's name for
`nonce`; if both appear, use `nonce`.

## 9. Payload shapes

`PersonalDetails`: `first_name`, `last_name`, `middle_name?`, `birth_date`
(DD.MM.YYYY), `gender`, `country_code` (ISO 3166-1 alpha-2),
`residence_country_code`, `first_name_native`, `last_name_native`,
`middle_name_native?`.

`IdDocumentData`: `document_no`, `expiry_date?` (DD.MM.YYYY).

`ResidentialAddress`: `street_line1`, `street_line2?`, `city`, `state?`,
`country_code`, `post_code`.

## 10. Service setup

`openssl genrsa 2048 > private.key` — **keep it secret**. Publish the public key
to the bot with `/setpublickey` and a privacy policy URL with `/setprivacypolicy`;
users see that URL when offered authorization.

## 11. Re-requesting corrected data

If received data is rejected, the service reports errors so the user can fix and
resend. Bot API: `setPassportDataErrors`. MTProto:
`users.setSecureValueErrors` (`secureValueErrorData` / `…FrontSide` /
`…ReverseSide` / `…Selfie` / `…File` / `…Files` / `…TranslationFile` /
`…TranslationFiles` / `secureValueError`). The user cannot resend until the
errors are fixed.

## 12. Conformance checklist for this package

Re-audited 2026-09-26 against this reference, with a test per normative step
(`tests/Passport/PassportSpecConformanceTest.php`,
`PassportNonceConformanceTest.php`, `PassportScopeConformanceTest.php`).
Anything marked ✗ is a known deviation, stated as one — not a claim.

- [x] RSA **OAEP** only for the credentials secret — and a PKCS#1 v1.5 seal is
      refused, so there is no fallback to distinguish padding modes with
- [x] `SHA512(secret + hash)` → key `[0,32]`, iv `[32,16]`, raw, concatenation
      order `secret + hash`
- [x] AES-256-CBC with **no implicit block padding** (`OPENSSL_ZERO_PADDING`) —
      was ✗ until this audit: `OPENSSL_RAW_DATA` alone made OpenSSL strip a
      PKCS#7 block upstream never writes, which rejects real ciphertext
- [x] `hash_equals` integrity check over the AES input, with the padding still
      attached, before the padding is removed
- [x] **32–255 byte padding is stripped**, first byte = padding length
      INCLUDING itself, and a first byte outside 32..255 (or longer than the
      payload) is refused rather than silently truncating — was ✗: nothing
      stripped padding, and `json_decode` hid it by ignoring trailing bytes
- [x] `nonce` is CSPRNG (128-bit `random_bytes`), single-use, expiring, and a
      nonce the flow never issued is refused on BOTH the attach flow and the
      anonymous login flow. **Stated precisely:** the anonymous flow compares
      `hash_equals` over `sha256(nonce)` digests (constant time); the attach
      flow enforces it with a one-time keyed registry lookup, which is
      single-use rather than a byte-by-byte comparison.
- [x] `scope` is the §8 object `{"v": 1, "data": [...]}` with `v` = 1, carries
      only scope elements, expands `id_document`/`address_document`, and drops
      an unknown entry rather than forwarding it. `email` and `phone` ARE
      scope element types (see §6/C3) and are emitted under the Bot API's own
      spelling — the phone as `phone_number` — so a host that configured `phone`
      still asks Telegram for the right thing.
- [x] **Verified email/phone are read ONLY from the Telegram-vetted location.**
      The three coordinates this package used to read — `secure_data.email.address`,
      `secure_data.phone.phone_number`, and the decrypted blob's own top-level
      `email`/`phone` — are gone from the code, and the blob's top-level
      scalars are not read at all. Per §5 `secure_data` may carry only the 11
      document types plus `personal_details`, and `PassportValueValidator`
      REFUSES any other key before the host's allow-list is even consulted,
      so a host cannot opt back into a non-spec field. Per §6 the verified
      values arrive as plaintext on the matching `EncryptedPassportElement` in
      `PassportData.data[]`, and that is the single place an identity is read
      from (`PassportVerifiedClaims`). The element's `type` is what selects the
      field, an element's encrypted `data` sub-object is never descended into,
      and two elements of one claim type that DISAGREE yield no claim at all
      rather than a winner. Provenance: `tests/Passport/PassportIdentityIntakeTest.php`
      builds real `PassportData` objects via `PassportElementFactory`, and
      pins the three removed coordinates as negatives.
- [x] **The MTProto claim channel is implemented, and it reads the same §6
      coordinate the Bot API side reads.** §6 also places the verified
      email/phone at `secureValue.plain_data` (`securePlainEmail` /
      `securePlainPhone`), on the `values: Vector<SecureValue>` sibling of
      `SecureCredentialsEncrypted`. `SecureValueTraversal::fromValues()`
      (`src/Teleframe/Passport/SecureValueTraversal.php:146`) walks that
      vector — the unconditional `type`, the nested `data`, `front_side`,
      `reverse_side`, `selfie`, the `translation` and `files` vectors, and
      `plain_data` — recording every branch it reaches in
      `branchesVisited()`, and `PassportVerifiedClaims::fromSecureValues()`
      (`src/Teleframe/Passport/PassportVerifiedClaims.php:221`) hands the
      result to the SAME value object `fromPassportData()` produces, so
      nothing downstream can tell the two shapes apart. Two rules keep it
      honest rather than convenient: a leaf becomes a claim only under the §7
      type table (`secureValueTypeEmail` / `secureValueTypePhone` only) and
      only from a `securePlainEmail` / `securePlainPhone`, and two leaves that
      disagree yield NO claim — the same rule the Bot API element list already
      followed. A walk that finds no claim still yields `none()` and a
      field-mode login on it is still refused loudly.
      *(This entry was `[ ]` — "NOT implemented" — until `068a8829`. The
      previous text is preserved in the commit that replaced it; the claim in
      `PassportVerifiedClaims`' docblock that the traversal was ABSENT was
      false the moment the traversal landed, and the spec for it records the
      same hand-off:
      `docs/superpowers/specs/2026-09-27-mtproto-securevalue-claim-traversal.md:129-132`.)*
- [ ] Full `SecureValue` **document** surface is still NOT implemented, and the
      distinction that matters is walked-vs-decrypted: the traversal now
      REACHES `front_side`, `reverse_side`, `selfie`, `translation` and
      `files` and COUNTS them, but it never fetches or decrypts them — no
      `getFile` download, no `PersonalDetails` / `IdDocumentData` /
      `ResidentialAddress` modelling, and the nested `SecureData.data` is
      still an opaque AES payload that the intake path does not decrypt. The
      nested CRYPTO primitive exists and is spec-conformant
      (`PassportDecryptor::decryptDataCredentials` /
      `decryptFileCredentials`); what is still missing is a traversal that
      feeds it with bytes.
- [ ] Error reporting / re-request is NOT implemented as an orchestrated
      feature: no `setPassportDataErrors` (Bot API) and no
      `users.setSecureValueErrors` (MTProto) call. `PassportFieldError` /
      `PassportAuthorizationForm` only READ the per-field errors Telegram
      returns in an authorization form. There is no way for a host to tell
      Telegram a field was rejected, so a user cannot be asked to correct and
      resend.

**This package is identity-fields-only.** It is not full Telegram Passport and
must not be presented as such. Concretely, and as a list of things that do NOT
work:

- **No document or file surface.** The traversal REACHES `front_side`,
  `reverse_side`, `selfie`, `translation` and `files` and counts them, but it
  does not download them with `getFile`, does not decrypt them, and does not
  validate them; there is no `PersonalDetails`, `IdDocumentData` or
  `ResidentialAddress` value objects (§9), and the nested `SecureData.data`
  is not decrypted on the intake path. The nested CRYPTO primitive exists and
  is spec-conformant (`PassportDecryptor::decryptDataCredentials` /
  `decryptFileCredentials`) but nothing feeds it bytes. The one document field
  this package models is `personal_details.first_name`.
- **No payload modelling.** A document arrives as an opaque entry and its
  contents are never read.
- **No re-request path.** No `setPassportDataErrors` (Bot API) and no
  `users.setSecureValueErrors` (MTProto) call. `PassportFieldError` /
  `PassportAuthorizationForm` only READ the per-field errors Telegram returns
  in an authorization form, so a host cannot tell Telegram a field was
  rejected and a user cannot be asked to correct and resend.
- **The MTProto claim channel IS read** (§6, and the `[x]` entry above), and
  the login chain reads the sender from `message.from_id` as a `peerUser` Peer,
  which is the field a real `updateNewMessage` actually carries. `peerChat` /
  `peerChannel` / an unnameable peer yield no user id, which is the MTProto
  spelling of the existing `sender_chat` refusal, and it refuses loudly. What
  remains unproven is the same thing it always was: **no live run exists**, so
  this is verified against the vendored schema, not against Telegram.
- **`secure_data` is validated for SHAPE, not content.** A present §5 type is
  checked against the host allow-list and, for `personal_details`, for the one
  required plain field. Nothing deeper is verified, because nothing deeper is
  read.

The verified email/phone channel IS wired to where Telegram actually puts
those values, and it is wired on **BOTH** §6 shapes — the Bot API element
plaintext and the MTProto `SecureValue.plain_data` — but only for the identity
fields, and only as a COUNT-and-claim walk on the MTProto side. Everything
above remains absent, and adding any of it is a redesign rather than a patch.

**And the standing limit that no line above may be read past: nothing in this
module has been run against live Telegram.** Every fixture here is derived
from this reference and the committed `api.tl` / generated schema types, which
defends against our own misreading and is not evidence about Telegram's. There
is no live Passport gate — not opt-in, not otherwise.
