<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

/**
 * PassportVerifiedClaims — the Telegram-VERIFIED email and phone a Passport
 * response carries, read from the ONE place the specification puts them.
 *
 * `docs/reference/telegram-passport-official.md` §6 is unambiguous, and it is
 * the reason this class exists:
 *
 *   - **Bot API:** `phone_number` and `email` are returned as **plaintext
 *     strings** on the `EncryptedPassportElement` — i.e. as fields of the
 *     entries in `PassportData.data[]`, whose `type` is `"email"` or
 *     `"phone_number"`. They are OUTSIDE the encrypted blob (the element
 *     carries them beside its own `data`/`hash`), and the Bot API field
 *     documentation says so in as many words: "User's verified email
 *     address; available only for "email" type". "Emails and phone numbers
 *     sent using telegram passport are *already verified*."
 *   - **MTProto:** they arrive in `secureValue.plain_data` as
 *     `securePlainEmail` / `securePlainPhone`.
 *
 * and §5 says what is inside the blob, which is NOT them: `secure_data` keys
 * are the document types plus `personal_details`. There is no
 * `secure_data.email` and no `secure_data.phone`, and the `Credentials` JSON
 * itself is `{secure_data, nonce}` — no top-level `email`/`phone` either.
 *
 * So the previous shape this package read (`secure_data.email.address`,
 * `secure_data.phone.phone_number`, and the decrypted JSON's own top-level
 * `email`/`phone`) was not "an alternative location". It was three locations
 * Telegram never writes, which is why email/phone login could not work against
 * real Telegram data at all.
 *
 * ══ BOTH SHAPES ARE NOW READ, AND BOTH FROM §6 ═══════════════════════════
 *
 * §6 names two places and no others, and this class now reads BOTH:
 *
 *   - **Bot API** — the plaintext on the `EncryptedPassportElement` entries of
 *     `PassportData.data[]`, via `fromPassportData()`. Unchanged, and still
 *     the shape the shipped webhook intake uses.
 *   - **MTProto** — `secureValue.plain_data` as `securePlainEmail` /
 *     `securePlainPhone`, on the `values: Vector<SecureValue>` sibling of the
 *     `SecureCredentialsEncrypted`, via `fromSecureValues()`, which delegates
 *     the whole §7 `SecureValue` walk to `SecureValueTraversal`.
 *
 * Both are Telegram-vetted locations and both yield this SAME value object, so
 * a field-mode login on an MTProto-shaped response resolves through the same
 * claim-based path a Bot API one does, and the rest of the module is
 * unchanged. What the claim alone does NOT deliver, and what a reader must
 * not infer from it: the login chain has a SECOND edge, the sender's telegram
 * user id, and the two shapes spell it differently (`message.from.id` on the
 * Bot API, `message.from_id` as a `peerUser` Peer on MTProto). That edge is
 * `SenderUserId`'s, and until it read both spellings this module's "the same
 * login in two shapes" was not true of the login — only of the claim. What
 * has NOT changed, and must not: a walk that finds no claim still yields
 * `none()`, and a field-mode login on that is still REFUSED loudly. A shape
 * yielding no claim is never downgraded onto some other coordinate — the
 * traversal refuses a `plain_data` on a type §7 does not allow one on (and
 * fails CLOSED on a type it cannot name at all), and refuses to pick a winner
 * between two disagreeing leaves, rather than resolving a login from a value
 * nobody vouched for.
 *
 * ══ A COORDINATE THAT LOOKS RIGHT AND IS NOT ════════════════════════════
 *
 * `PassportData` has exactly TWO fields — `data` (an
 * `Array of EncryptedPassportElement`) and `credentials` (an
 * `EncryptedCredentials`). It has NO top-level `email` and NO top-level
 * `phone_number`, so a reader that looks for those two names beside
 * `credentials` finds nothing on a genuine response. An earlier revision of
 * this class did precisely that, on a reading of the reference that had the
 * substance right ("plaintext, already verified, not inside `secure_data`")
 * and the coordinate wrong. It was a false green: the suite built fixtures in
 * the invented shape, so the suite and the code agreed while both were
 * unreachable from real Telegram. The shape is therefore pinned from the
 * vendored Bot API type (`src/Schema/schema/sources/botapi-spec.json`) and
 * the test fixtures are built as real `PassportData` objects, `data` array
 * and all.
 *
 * SECURITY: the values are personal data. They are never logged, never put in
 * an exception message, never put in a URL, a redirect or a response body by
 * anything in this module — this object is a carrier, and a refusal names the
 * TYPE (`email`, `phone`) only, never the value.
 *
 * Plain-PHP constructible (standalone-smoke): a private constructor with two
 * named static factories and two accessors. Zero regex — `is_string` and
 * `trim` only, because `src/Teleframe` is a strictly zero-regex tree.
 */
final class PassportVerifiedClaims
{
    /**
     * §6 — the two `EncryptedPassportElement.type` values that carry a
     * verified identity, and the two field names they carry it in. Spelled out
     * here (not shared with `PassportConfig`) because this is the READ side
     * and that is the REQUEST side: they must agree by specification, not by
     * a constant, and a fixture that reads the same constant it is testing
     * cannot fail.
     */
    private const EMAIL_TYPE = 'email';

    private const PHONE_TYPE = 'phone_number';

    private function __construct(
        private readonly ?string $email,
        private readonly ?string $phone,
    ) {}

    /** No verified claim at all — the MTProto shape, or a document-only response. */
    public static function none(): self
    {
        return new self(null, null);
    }

    /**
     * §6 — the Bot API shape. `$passportData` is the `PassportData` object
     * itself, i.e. the value of `message.passport_data`.
     *
     * The verified values are read off the entries of its `data` array, by
     * `type`: an `EncryptedPassportElement` whose `type` is `"email"` carries
     * the verified address in its own `email` field, and one whose `type` is
     * `"phone_number"` carries it in `phone_number`. Both are plaintext and
     * both sit BESIDE that element's `data`/`hash` — never inside the
     * encrypted blob.
     *
     * Three rules, each of which is a refusal rather than a fallback:
     *
     *  - an element's `type` is what selects the field. A value found on an
     *    element of some OTHER type is not this package's claim and is not
     *    read, so a `personal_details` element carrying an incidental `email`
     *    cannot become an identity.
     *  - a `data` sub-object is never descended into. A value inside the
     *    encrypted element data is exactly the non-spec read this class
     *    exists to remove.
     *  - TWO elements of the same claim type disagreeing is a protocol
     *    violation (Telegram sends at most one of each), and the claim is
     *    dropped rather than resolved to a winner. Dropping it means the
     *    service layer refuses loudly, which is the correct outcome; picking
     *    one would let an attacker who can append a second element choose
     *    which identity a login resolves.
     *
     * A non-string or blank value is likewise treated as absent rather than
     * as a claim: this package never derives an identity from a field it
     * could not read as the exact string Telegram sent.
     *
     * @param  array<string, mixed>  $passportData
     */
    public static function fromPassportData(array $passportData): self
    {
        $elements = $passportData['data'] ?? null;
        if (! is_array($elements)) {
            return self::none();
        }

        $email = null;
        $phone = null;
        $emailConflict = false;
        $phoneConflict = false;

        foreach ($elements as $element) {
            if (! is_array($element)) {
                continue;
            }

            $type = $element['type'] ?? null;

            if ($type === self::EMAIL_TYPE) {
                $value = self::readableString($element['email'] ?? null);
                if ($value === null) {
                    continue;
                }
                if ($email !== null && $email !== $value) {
                    $emailConflict = true;
                }
                $email ??= $value;
            } elseif ($type === self::PHONE_TYPE) {
                $value = self::readableString($element['phone_number'] ?? null);
                if ($value === null) {
                    continue;
                }
                if ($phone !== null && $phone !== $value) {
                    $phoneConflict = true;
                }
                $phone ??= $value;
            }
        }

        return new self(
            $emailConflict ? null : $email,
            $phoneConflict ? null : $phone,
        );
    }

    /**
     * §6 — the same two fields, read straight off a webhook update. Null when
     * the update carries no `message.passport_data` object at all (an MTProto
     * update, or a root `SecureCredentialsEncrypted`) — those are read through
     * `fromSecureValues()` on the `values` sibling instead.
     *
     * @param  array<string, mixed>  $update
     */
    public static function fromUpdate(array $update): ?self
    {
        $passportData = $update['message']['passport_data'] ?? null;
        if (! is_array($passportData)) {
            return null;
        }

        return self::fromPassportData($passportData);
    }

    /**
     * §6, MTProto — the SECOND named constructor, off a
     * `values: Vector<SecureValue>`.
     *
     * The verified values are `secureValue.plain_data` as `securePlainEmail` /
     * `securePlainPhone`, and the entire §7 walk (the nested `SecureData`, the
     * `front_side`/`reverse_side`/`selfie`/`translation`/`files` branches) is
     * `SecureValueTraversal`'s. Nothing else changes downstream: this returns
     * the same value object `fromPassportData()` returns, so `PassportPayload`,
     * the resolvers and the provenance recorder cannot tell the two shapes
     * apart — which is the point, because §6 says the two are the same
     * guarantee.
     *
     * A value list that is absent, empty, or that carries no `plain_data`
     * yields `none()`, never a fallback onto another coordinate.
     */
    public static function fromSecureValues(mixed $values): self
    {
        return SecureValueTraversal::fromValues($values)->claims();
    }

    /**
     * The carrier factory the §6 readers share: two already-vetted values, or
     * nulls, and nothing else is derivable. `SecureValueTraversal` resolves
     * its `plain_data` leaves here so the two §6 shapes cannot drift into two
     * slightly different conflict rules.
     */
    public static function fromClaimValues(?string $email, ?string $phone): self
    {
        return new self($email, $phone);
    }

    /** The verified email, or null when Telegram sent none. */
    public function email(): ?string
    {
        return $this->email;
    }

    /** The verified phone, or null when Telegram sent none. */
    public function phone(): ?string
    {
        return $this->phone;
    }

    public function hasEmail(): bool
    {
        return $this->email !== null;
    }

    public function hasPhone(): bool
    {
        return $this->phone !== null;
    }

    /**
     * The claim TYPES present, for provenance and refusal messages — type
     * NAMES only, never values (`fieldsVerified`, the `require_verified`
     * refusal, the ambiguity refusal).
     *
     * @return list<string>
     */
    public function types(): array
    {
        $types = [];
        if ($this->email !== null) {
            $types[] = 'email';
        }
        if ($this->phone !== null) {
            $types[] = 'phone';
        }

        return $types;
    }

    /**
     * A non-empty, non-blank string, or null. Never trims the returned value:
     * the identity comparison is exact, so the bytes Telegram sent are the
     * bytes compared (a blank check is all that is done with them).
     */
    private static function readableString(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return $value;
    }
}
