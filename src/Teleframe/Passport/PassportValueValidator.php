<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

/**
 * PassportValueValidator — the §5 shape check + the host's type allow-list +
 * the one required plain field this package models (design §2/G3).
 *
 * `validate()` refuses a payload in two escalating steps, both loud
 * PassportLoginException, never a silent drop:
 *
 *  1. **§5 first, allow-list second.** A `secure_data` key outside §5's
 *     catalogue (`PassportPayload::SECURE_DATA_TYPES`) is a protocol
 *     violation and is refused whatever the host configured. This is the
 *     regression guard for the audit's central defect: the previous default
 *     allow-list shipped `['personal_details', 'email', 'phone']` and the
 *     previous validator read `secure_data.email.address`, so a payload whose
 *     identity lived at a coordinate Telegram never writes was ACCEPTED, while
 *     a real one (which carries its email in §6's location) was resolved as
 *     "no identity". Order matters — checking the allow-list first would let a
 *     host opt back into a non-spec key, which is not a host's decision to
 *     make: a host cannot configure Telegram's wire format.
 *  2. then the host's allow-list, and the required plain field of a present
 *     §5 type.
 *
 * Both steps above walk `secure_data` ONLY. The §6 verified claims — the
 * plaintext `email` / `phone_number` Telegram vets, which is where an
 * identity ACTUALLY comes from — travel on a different door
 * (`PassportVerifiedClaims`), so they get their own allow-list gate below the
 * `secure_data` loop. Without it the host's `allowed_types` read as a total
 * policy while excluding it had no effect at all on the claim channel: a
 * payload carrying only a verified email passed `validate($payload, [])`, an
 * allow-list that permits nothing at all.
 *
 * `plainField()` is the validator-side read of a §5 type's plain scalar
 * (`personal_details.first_name`). It is NOT how an email or a phone is read:
 * those are §6 plaintext claims (`PassportPayload::plainEmail()` /
 * `plainPhone()`), never `secure_data` entries.
 *
 * ZERO regex: str_* / array funcs only (phpstan spaze governs).
 */
final class PassportValueValidator
{
    /** @var array<string, string> §5 SecureValueType => required plain field */
    private const REQUIRED_FIELDS = [
        'personal_details' => 'first_name',
    ];

    /**
     * Validate a decrypted payload against the spec's §5 shape and the host's
     * allowed-type list. Throws PassportLoginException when:
     *  - a `secure_data` key is not a §5 type (refused — Telegram never
     *    writes it, so nothing may be read from it);
     *  - a §5 type is present but not in $allowedTypes (refused);
     *  - a present type with a required plain field is missing that field; or
     *  - a §6 VERIFIED CLAIM (`email` / `phone`) is present but not in
     *    $allowedTypes (refused).
     *
     * The claim gate exists because the §5 loop above iterates ONLY
     * `verifiedSecureDataTypes()`. §6 claims arrive on a different door — the
     * `PassportVerifiedClaims` from the intake, never a `secure_data` key — so
     * without a second loop the host's policy covered the document types and
     * silently did not cover the channel an identity actually resolves from.
     *
     * $allowedTypes is ALLOW-LIST, NEVER DENY-LIST: an empty list means ALLOW
     * NONE and every present type or claim is refused loud. That is the
     * pre-existing semantic of the §5 loop and the claim loop mirrors it
     * rather than inventing a second meaning for the same argument.
     *
     * @param  array<int, string>  $allowedTypes
     */
    public function validate(PassportPayload $payload, array $allowedTypes): void
    {
        foreach ($payload->verifiedSecureDataTypes() as $type) {
            // §5 — the shape check, before any host configuration is read.
            if (! in_array($type, PassportPayload::SECURE_DATA_TYPES, true)) {
                throw new PassportLoginException(
                    sprintf(
                        'Telegram Passport secure_data carries "%s", which is not a SecureData type Telegram sends '
                        .'(the verified email and phone arrive as the PassportData plaintext fields, not inside '
                        .'secure_data): the payload is refused.',
                        $type
                    )
                );
            }

            if (! in_array($type, $allowedTypes, true)) {
                throw new PassportLoginException(
                    sprintf('Telegram Passport type "%s" is not allowed by the host.', $type)
                );
            }

            $required = self::REQUIRED_FIELDS[$type] ?? null;
            if ($required !== null && $payload->plainValue($type) === null) {
                throw new PassportLoginException(
                    sprintf('Telegram Passport type "%s" is missing its required field "%s".', $type, $required)
                );
            }
        }

        // THE §6 CLAIM CHANNEL IS A SECOND DOOR, SO IT GETS ITS OWN GATE.
        // `verifiedSecureDataTypes()` above only ever yields `secure_data` keys,
        // so an allow-list that permitted NOTHING still waved through an
        // MTProto `securePlainEmail` claim and a Bot API `email`-typed
        // PassportData element — the host's policy read as configured while the
        // login resolved an identity from a type the host had excluded. Both
        // §6 shapes (`secureValue.plain_data` and `PassportData.data[]`) land
        // here, because both arrive as one PassportVerifiedClaims and are
        // indistinguishable downstream by construction.
        //
        // The comparison is strict, exactly like the secure_data refusal above:
        // `'0'` is never `0`, and no loose match can smuggle a type past the
        // allow-list. The empty-list semantics are the ones the secure_data
        // loop already had — an empty allow-list is ALLOW NONE (refuse all),
        // NOT allow-all, and a claim is refused exactly as a document type is.
        foreach ($payload->claims()->types() as $claimType) {
            if (! in_array($claimType, $allowedTypes, true)) {
                throw new PassportLoginException(
                    sprintf(
                        'Telegram Passport verified claim type "%s" is not allowed by the host.',
                        $claimType
                    )
                );
            }
        }
    }

    /**
     * The §5 type's plain scalar per the shipped secure-data shape
     * (`personal_details.first_name`). Null for an absent type, a non-§5 type,
     * or a field this package does not model. Never an email or a phone.
     */
    public function plainField(PassportPayload $payload, string $type): ?string
    {
        return $payload->plainValue($type);
    }
}
