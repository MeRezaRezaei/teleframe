<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Passport;

use MeRezaRezaei\Teleframe\Passport\PassportVerifiedClaims;

/**
 * PassportElementFactory — builds the `EncryptedPassportElement` list that a
 * real Bot API `PassportData` carries in its `data` field.
 *
 * WHY THIS EXISTS, AND WHY IT IS A FIXTURE AND NOT A CONSTANT READ. The
 * re-plumb that moved this package's verified identity reads onto the
 * specification's own coordinate went wrong once already, and it went wrong
 * in exactly the way `SpecCredentialFactory` was written to prevent: the
 * coordinate was taken from a prose reading rather than from the vendored Bot
 * API type, and then the TESTS were written to match the code. The suite went
 * green against a `PassportData` that Telegram never sends — the fixture and
 * the implementation agreed, and both were unreachable.
 *
 * So the shape is built here, in ONE place, from the Bot API field
 * documentation (vendored at `src/Schema/schema/sources/botapi-spec.json`):
 *
 *   PassportData.data  : Array of EncryptedPassportElement
 *   PassportData.credentials : EncryptedCredentials   <- the ONLY other field
 *
 *   EncryptedPassportElement.type          : "personal_details" | ... |
 *                                             "phone_number" | "email"
 *   EncryptedPassportElement.email         : "User's VERIFIED email address;
 *                                             available only for `email` type"
 *   EncryptedPassportElement.phone_number  : "User's VERIFIED phone number;
 *                                             available only for `phone_number`
 *                                             type"
 *
 * `PassportData` has no top-level `email` and no top-level `phone_number`, so
 * a fixture that puts them beside `credentials` is testing a coordinate that
 * does not exist. This factory cannot express that, which is the point.
 *
 * A test that needs a document element as well says so explicitly:
 * `elements(['personal_details' => [...], 'email' => '...'])`, where a key with
 * an array value is passed through as a caller-built element and a key with a
 * string value is treated as a verified claim. The two are kept apart on
 * purpose — conflating them is the defect being guarded.
 *
 * Zero regex; plain PHP; no keys, no disk, no container.
 */
final class PassportElementFactory
{
    /** The element types that carry a verified identity, per §6. */
    public const EMAIL_TYPE = 'email';

    public const PHONE_TYPE = 'phone_number';

    /**
     * The `PassportData.data` array for a bag of claims/documents.
     *
     * @param  array<string, mixed>  $claims  `email` / `phone_number` => string
     *                                        (a verified claim), or any other
     *                                        key => array (a document element,
     *                                        passed through as built)
     * @return list<array<string, mixed>>
     */
    public static function elements(array $claims): array
    {
        $elements = [];

        foreach ($claims as $key => $value) {
            // A document element: the caller built the whole entry.
            if (is_array($value)) {
                if (! isset($value['type'])) {
                    $value['type'] = $key;
                }
                $elements[] = $value;

                continue;
            }

            // A verified claim: the spec types it and names the field.
            if ($key === self::EMAIL_TYPE) {
                $elements[] = ['type' => self::EMAIL_TYPE, 'email' => $value];

                continue;
            }

            if ($key === self::PHONE_TYPE) {
                $elements[] = ['type' => self::PHONE_TYPE, 'phone_number' => $value];
            }
        }

        return $elements;
    }

    /**
     * A whole real-shaped `PassportData` object — the element list plus the
     * encrypted credentials envelope, in the order the Bot API documents them.
     *
     * @param  array{data: string, hash: string, secret: string}  $envelope
     * @param  array<string, mixed>  $claims
     * @return array{data: list<array<string, mixed>>, credentials: array<string, string>}
     */
    public static function passportData(array $envelope, array $claims = []): array
    {
        return [
            'data' => self::elements($claims),
            'credentials' => $envelope,
        ];
    }

    /**
     * The verified claims a bag would produce, for a test that builds a
     * `PassportPayload` directly and therefore has no `PassportData` object to
     * hand. Goes through `elements()` so the spec coordinate is still built in
     * exactly one place — a helper that invented a second way of expressing
     * the claim is how this shape drifted in the first place.
     *
     * @param  array<string, mixed>  $claims
     */
    public static function claims(array $claims = []): PassportVerifiedClaims
    {
        return PassportVerifiedClaims::fromPassportData(['data' => self::elements($claims)]);
    }
}
