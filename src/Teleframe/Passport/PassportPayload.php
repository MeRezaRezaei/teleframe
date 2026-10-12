<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

/**
 * PassportPayload — the DECRYPTED credentials result, kept strictly separate
 * from the PLAINTEXT verified claims that travel beside them.
 *
 * TWO SURFACES, TWO SOURCES, NEVER CONFLATED (this class is the fix for the
 * audit's central defect — see §5/§6 of
 * `docs/reference/telegram-passport-official.md`):
 *
 *  1. `secure_data` — inside the encrypted blob. Per §5 its keys are the
 *     DOCUMENT types plus `personal_details`, and nothing else:
 *     `personal_details`, `passport`, `internal_passport`, `driver_license`,
 *     `identity_card`, `address`, `utility_bill`, `bank_statement`,
 *     `rental_agreement`, `passport_registration`, `temporary_registration`.
 *     This class reads exactly those (see `SECURE_DATA_TYPES`) and NO others.
 *     There is no `secure_data.email` and no `secure_data.phone` in the
 *     specification, and a payload carrying one is refused by
 *     `PassportValueValidator` — loudly, before any identity lookup.
 *
 *  2. the verified email / phone — OUTSIDE the blob. Per §6 the Bot API sends
 *     them as plaintext on the `EncryptedPassportElement` entries in
 *     `PassportData.data[]` — an element whose `type` is `email` carries
 *     `email`, one typed `phone_number` carries `phone_number` — and per §6
 *     they are "already verified" by Telegram. They arrive here as a
 *     `PassportVerifiedClaims` handed in by the INTAKE
 *     (`EncryptedCredentials::claims()`), never re-derived from the decrypted
 *     array.
 *
 * WHAT THIS CLASS DELIBERATELY NO LONGER DOES. The previous version read
 * `secure_data.email.address`, `secure_data.phone.phone_number` AND the
 * decrypted JSON's own top-level `email`/`phone`. None of those is a location
 * Telegram populates, so the whole identity path rested on fields that do not
 * exist, and a real response — which carries the values in §6's location — was
 * resolved as "no identity". It also refused to read a legitimate location.
 * The `Credentials` JSON is `{secure_data, nonce}` (§5), so a top-level
 * `email`/`phone` inside the decrypted blob is a NON-SPEC shape; it is
 * recorded by NAME in `strayClaimTypes()` for diagnostics and never read.
 * An identity can therefore no longer come from anywhere but §6.
 *
 * NONCE PRESENCE IS ENFORCED at construction: a decrypted payload without a
 * nonce is a protocol violation — loud PassportLoginException, never a
 * silent drop (design §2 "nonce presence enforced at construction", §3).
 *
 * All reads are array/string-typed — zero-regex.
 */
final class PassportPayload
{
    /**
     * §5 — the ONLY keys `secure_data` may carry. A key outside this set is a
     * protocol violation and is refused, whatever the host's allow-list says:
     * a host cannot opt into a field Telegram does not send, and an allow-list
     * containing `email`/`phone` (the shipped default still does, for
     * backwards compatibility) must not re-open the defect.
     *
     * @var list<string>
     */
    public const SECURE_DATA_TYPES = [
        'personal_details',
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

    /**
     * §9 — the one plain scalar this package reads out of `secure_data`.
     * `personal_details` is the only §5 type whose payload this package
     * models; every other document type's plaintext lives in a nested
     * `SecureData.data` that must be decrypted separately (§4) and is
     * documented as absent.
     *
     * @var array<string, string> type => plain value key
     */
    private const PLAIN_KEYS = [
        'personal_details' => 'first_name',
    ];

    /** @var array<string, mixed> */
    private readonly array $secureData;

    private readonly string $nonce;

    private readonly PassportVerifiedClaims $claims;

    /** @var list<string> */
    private readonly array $strayClaimTypes;

    /**
     * @param  array<string, mixed>  $decrypted  PassportDecryptor::decryptCredentials() output
     * @param  PassportVerifiedClaims|null  $claims  the §6 plaintext claims from
     *                                               the intake; null means "this
     *                                               response carried none", which is
     *                                               NOT the same as reading them
     *                                               out of the blob
     */
    public function __construct(array $decrypted, ?PassportVerifiedClaims $claims = null)
    {
        $nonce = $decrypted['nonce'] ?? null;
        if (! is_string($nonce) || $nonce === '') {
            throw new PassportLoginException(
                'Decrypted passport credentials carry no nonce: the payload is refused.'
            );
        }

        $secureData = $decrypted['secure_data'] ?? [];
        $this->secureData = is_array($secureData) ? $secureData : [];
        $this->nonce = $nonce;
        $this->claims = $claims ?? PassportVerifiedClaims::none();
        $this->strayClaimTypes = self::detectStrayClaimTypes($decrypted, $this->secureData);
    }

    /** The host-issued nonce tying this response to the initiating session. */
    public function nonce(): string
    {
        return $this->nonce;
    }

    /** Whether the decrypted secure_data carries the given §5 SecureValueType. */
    public function hasType(string $type): bool
    {
        return isset($this->secureData[$type]);
    }

    /**
     * A §5 document type's plain (decrypted-readable) scalar, per the shipped
     * secure-data shape — currently `personal_details.first_name` and nothing
     * else. Null for a type that is absent, not a §5 type, or has no plain
     * field this package models.
     *
     * It NEVER returns an email or a phone: those are not `secure_data` types
     * (§5) and a resolver asking for one gets null, which is the refusal.
     * `plainEmail()` / `plainPhone()` are the only ways to read the verified
     * claims, and they read §6's location.
     */
    public function plainValue(string $type): ?string
    {
        $entry = $this->secureData[$type] ?? null;
        if ($entry === null) {
            return null;
        }
        if (is_string($entry) && $entry !== '') {
            return $entry;
        }
        if (! is_array($entry)) {
            return null;
        }

        $key = self::PLAIN_KEYS[$type] ?? null;
        if ($key === null) {
            return null;
        }
        $value = $entry[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    /** §6 — the Telegram-verified plaintext email, or null. */
    public function plainEmail(): ?string
    {
        return $this->claims->email();
    }

    /** §6 — the Telegram-verified plaintext phone, or null. */
    public function plainPhone(): ?string
    {
        return $this->claims->phone();
    }

    /** The claims object itself, for the one caller that needs both. */
    public function claims(): PassportVerifiedClaims
    {
        return $this->claims;
    }

    /**
     * The verified `secure_data` type NAMES — the §5 document types Telegram
     * actually sent. Evidence for the host, never storage of a value.
     *
     * @return list<string>
     */
    public function verifiedSecureDataTypes(): array
    {
        $types = [];
        foreach (array_keys($this->secureData) as $type) {
            if (is_string($type) && $type !== '') {
                $types[] = $type;
            }
        }

        return $types;
    }

    /**
     * The verified `secure_data` types PLUS the §6 claim types, as one list of
     * NAMES. This is what `LoginResult::fieldsVerified` and the provenance
     * recorder carry: a login confirmed by Telegram's plaintext email IS
     * evidence of a verified email, and recording only the document types
     * would understate it.
     *
     * @return list<string>
     */
    public function verifiedFieldTypes(): array
    {
        return array_values(array_unique(array_merge(
            $this->verifiedSecureDataTypes(),
            $this->claims->types(),
        )));
    }

    /**
     * The identity claim TYPES found at a location the specification does not
     * use — the old shape. TYPE NAMES ONLY, never the values: a refusal that
     * names the offending field must not carry the field.
     *
     * Two non-spec locations are recognised, both of which the previous
     * implementation read as identity:
     *   - a top-level `email`/`phone` in the decrypted blob (§5 says the
     *     `Credentials` JSON is `{secure_data, nonce}`);
     *   - a `secure_data.email` / `secure_data.phone` entry (§5's key list has
     *     neither).
     *
     * @return list<string>
     */
    public function strayClaimTypes(): array
    {
        return $this->strayClaimTypes;
    }

    /**
     * The decrypted blob's `secure_data` table plus the §6 claims, under the
     * specification's own field names.
     *
     * @return array{secure_data: array<string, mixed>, nonce: string, email: ?string, phone_number: ?string}
     */
    public function toArray(): array
    {
        return [
            'secure_data' => $this->secureData,
            'nonce' => $this->nonce,
            'email' => $this->claims->email(),
            'phone_number' => $this->claims->phone(),
        ];
    }

    /**
     * Names of the identity types present at a NON-SPEC location. Presence
     * only — the value is never read, stored or returned.
     *
     * @param  array<string, mixed>  $decrypted
     * @param  array<string, mixed>  $secureData
     * @return list<string>
     */
    private static function detectStrayClaimTypes(array $decrypted, array $secureData): array
    {
        $types = [];

        foreach (['email', 'phone'] as $type) {
            if (isset($decrypted[$type]) || isset($secureData[$type])) {
                $types[] = $type;
            }
        }

        return $types;
    }
}
