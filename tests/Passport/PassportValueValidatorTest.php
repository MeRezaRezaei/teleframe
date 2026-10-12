<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Passport;

use MeRezaRezaei\Teleframe\Passport\PassportLoginException;
use MeRezaRezaei\Teleframe\Passport\PassportPayload;
use MeRezaRezaei\Teleframe\Passport\PassportValueValidator;
use MeRezaRezaei\Teleframe\Passport\PassportVerifiedClaims;
use PHPUnit\Framework\TestCase;

/**
 * PassportValueValidatorTest — the G3 validation rules (design §2/§3) rebuilt
 * on the SPEC's §5 key list, which is what `secure_data` actually holds:
 * the document types plus `personal_details`, and NOTHING else.
 *
 * The previous version of this file asserted that a `secure_data.email`
 * entry with an `address` field was a VALID, ALLOWED, REQUIRED-FIELD type.
 * Per §5 no such type exists — Telegram sends the verified email as the
 * element plaintext in `PassportData.data[]` (§6) — so every one of those
 * assertions was pinning the defect. They are replaced below by the
 * §5 refusals, and by the two shapes that must still parse: `personal_details`
 * (the one modelled plain scalar) and a document type's presence.
 *
 * Nonce presence is enforced at PassportPayload CONSTRUCTION: a decrypted
 * payload without a nonce is a protocol violation, loud, never a silent drop.
 */
final class PassportValueValidatorTest extends TestCase
{
    public function test_payload_without_nonce_throws_at_construction(): void
    {
        $this->expectException(PassportLoginException::class);
        $this->expectExceptionMessage('no nonce');

        new PassportPayload([
            'secure_data' => ['email' => ['address' => 'ada@example.com']],
        ]);
    }

    public function test_payload_with_empty_nonce_throws_at_construction(): void
    {
        $this->expectException(PassportLoginException::class);

        new PassportPayload(['secure_data' => [], 'nonce' => '']);
    }

    public function test_validate_passes_the_spec_secure_data_types_it_allows(): void
    {
        // Every key below is in §5's list. `email`/`phone` are NOT and are
        // absent here on purpose — see the refusals further down.
        $payload = new PassportPayload([
            'secure_data' => [
                'personal_details' => ['first_name' => 'Ada', 'last_name' => 'Lovelace'],
                'passport' => ['type' => 'passport'],
                'driver_license' => ['type' => 'driver_license'],
                'utility_bill' => ['type' => 'utility_bill'],
            ],
            'nonce' => 'abc123',
        ]);

        (new PassportValueValidator)->validate(
            $payload,
            ['personal_details', 'passport', 'driver_license', 'utility_bill'],
        );

        $this->addToAssertionCount(1); // reached validate() without throwing
    }

    /**
     * THE REGRESSION GUARD FOR THE WHOLE DEFECT. §5 lists the `secure_data`
     * keys, and `email` is not one of them: the verified email is the
     * `PassportData` plaintext (§6). The previous validator ACCEPTED this
     * payload (the shipped default allow-list contained `email`), which is how
     * an identity could be read out of a field Telegram never writes — while a
     * real response, whose email is in §6's location, resolved as no identity
     * at all. It is refused now, and the allow-list is NOT consulted: a host
     * cannot opt into a key the protocol does not have.
     */
    public function test_validate_refuses_a_secure_data_email_key_even_when_the_host_allows_it(): void
    {
        $payload = new PassportPayload([
            'secure_data' => ['email' => ['address' => 'ada@example.com']],
            'nonce' => 'abc123',
        ]);

        $this->expectException(PassportLoginException::class);
        $this->expectExceptionMessage('not a SecureData type Telegram sends');

        // The host's own allow-list still says `email` — the shipped default
        // does — and the refusal stands anyway.
        (new PassportValueValidator)->validate($payload, ['personal_details', 'email', 'phone']);
    }

    public function test_validate_refuses_a_secure_data_phone_key_even_when_the_host_allows_it(): void
    {
        $payload = new PassportPayload([
            'secure_data' => ['phone' => ['phone_number' => '+15551234567']],
            'nonce' => 'abc123',
        ]);

        $this->expectException(PassportLoginException::class);
        $this->expectExceptionMessage('"phone"');

        (new PassportValueValidator)->validate($payload, ['personal_details', 'email', 'phone']);
    }

    public function test_validate_refuses_a_type_not_in_the_allow_list(): void
    {
        // `bank_statement` IS a §5 type, so this refusal is the host's own
        // allow-list and not the shape check.
        $payload = new PassportPayload([
            'secure_data' => ['bank_statement' => ['file' => []]],
            'nonce' => 'abc123',
        ]);

        $this->expectException(PassportLoginException::class);
        $this->expectExceptionMessage('"bank_statement" is not allowed by the host');

        (new PassportValueValidator)->validate($payload, ['personal_details', 'email', 'phone']);
    }

    public function test_validate_refuses_all_types_when_the_allow_list_is_empty(): void
    {
        $payload = new PassportPayload([
            'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
            'nonce' => 'abc123',
        ]);

        $this->expectException(PassportLoginException::class);

        (new PassportValueValidator)->validate($payload, []);
    }

    public function test_validate_requires_first_name_for_personal_details(): void
    {
        $payload = new PassportPayload([
            'secure_data' => ['personal_details' => ['last_name' => 'Lovelace']],
            'nonce' => 'abc123',
        ]);

        $this->expectException(PassportLoginException::class);
        $this->expectExceptionMessage('missing its required field "first_name"');

        (new PassportValueValidator)->validate($payload, ['personal_details']);
    }

    public function test_plain_field_extracts_the_one_plain_value_key_there_is(): void
    {
        $validator = new PassportValueValidator;
        $payload = new PassportPayload([
            'secure_data' => [
                'personal_details' => ['first_name' => 'Ada', 'last_name' => 'Lovelace'],
            ],
            'nonce' => 'abc123',
        ]);

        self::assertSame('Ada', $validator->plainField($payload, 'personal_details'));
        // A §5 document type is present but its plaintext lives in a nested
        // SecureData.data this package does not decrypt (documented absent),
        // so there is no plain scalar to read and null is the honest answer.
        self::assertNull($validator->plainField($payload, 'passport'));
    }

    public function test_plain_field_never_returns_an_email_or_a_phone(): void
    {
        // Even for a payload that DOES carry the old shape, no read of it
        // returns a value: there is no §5 `email` type, so `plainValue()` is
        // null. The claims come from §6 instead.
        $validator = new PassportValueValidator;
        $payload = new PassportPayload([
            'secure_data' => [
                'email' => ['address' => 'ada@example.com'],
                'phone' => ['phone_number' => '+15551234567'],
            ],
            'nonce' => 'abc123',
        ]);

        self::assertNull($validator->plainField($payload, 'email'));
        self::assertNull($validator->plainField($payload, 'phone'));
        self::assertNull($payload->plainEmail());
        self::assertNull($payload->plainPhone());
    }

    public function test_payload_reads_the_secure_data_types_and_the_section_6_claims_separately(): void
    {
        $payload = new PassportPayload(
            [
                'secure_data' => [
                    'personal_details' => ['first_name' => 'Ada'],
                    'passport' => ['type' => 'passport'],
                ],
                'nonce' => 'nonce-123',
            ],
            PassportElementFactory::claims(['email' => 'ada@example.com', 'phone_number' => '+15551234567']),
        );

        self::assertSame('nonce-123', $payload->nonce());
        self::assertTrue($payload->hasType('personal_details'));
        self::assertTrue($payload->hasType('passport'));
        self::assertFalse($payload->hasType('email'), 'email is not a §5 secure_data type');
        self::assertSame('Ada', $payload->plainValue('personal_details'));

        // The claims are the §6 plaintext, and they are what identity reads.
        self::assertSame('ada@example.com', $payload->plainEmail());
        self::assertSame('+15551234567', $payload->plainPhone());

        $row = $payload->toArray();
        self::assertSame('ada@example.com', $row['email']);
        self::assertSame('+15551234567', $row['phone_number']);

        self::assertSame(['personal_details', 'passport', 'email', 'phone'], $payload->verifiedFieldTypes());
    }

    public function test_a_payload_with_no_claims_reports_none_and_names_the_stray_ones_by_type(): void
    {
        $payload = new PassportPayload([
            'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
            'nonce' => 'n-1',
            // §5: the Credentials JSON is {secure_data, nonce}. These two are
            // NOT spec, and the old implementation read them as identity.
            'email' => 'top@example.com',
            'phone' => '+15551234567',
        ]);

        self::assertNull($payload->plainEmail());
        self::assertNull($payload->plainPhone());
        self::assertSame(['email', 'phone'], $payload->strayClaimTypes(), 'type NAMES only, never the values');

        $row = $payload->toArray();
        self::assertNull($row['email']);
        self::assertNull($row['phone_number']);
    }

    public function test_payload_to_array_shape(): void
    {
        $payload = new PassportPayload([
            'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
            'nonce' => 'n-1',
        ]);

        self::assertSame([
            'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
            'nonce' => 'n-1',
            'email' => null,
            'phone_number' => null,
        ], $payload->toArray());
    }

    public function test_the_section_5_key_catalogue_is_the_spec_list(): void
    {
        // Pinned verbatim from §5 so a future edit to the catalogue is a
        // visible test failure rather than a silent narrowing or widening of
        // what this package will read.
        self::assertSame([
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
        ], PassportPayload::SECURE_DATA_TYPES);
    }
}
