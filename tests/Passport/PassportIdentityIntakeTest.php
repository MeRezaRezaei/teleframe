<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Passport;

use Illuminate\Database\Eloquent\Model;
use MeRezaRezaei\Teleframe\Passport\EncryptedCredentials;
use MeRezaRezaei\Teleframe\Passport\FieldIdentityResolver;
use MeRezaRezaei\Teleframe\Passport\LoginResult;
use MeRezaRezaei\Teleframe\Passport\MemoryBindingWriter;
use MeRezaRezaei\Teleframe\Passport\MemoryNonceRegistry;
use MeRezaRezaei\Teleframe\Passport\MemoryPendingLoginRegistry;
use MeRezaRezaei\Teleframe\Passport\PassportAnonymousLoginService;
use MeRezaRezaei\Teleframe\Passport\PassportConfig;
use MeRezaRezaei\Teleframe\Passport\PassportKeyLoader;
use MeRezaRezaei\Teleframe\Passport\PassportLoginException;
use MeRezaRezaei\Teleframe\Passport\PassportLoginService;
use MeRezaRezaei\Teleframe\Passport\PassportVerifiedClaims;
use MeRezaRezaei\Teleframe\Passport\SessionFingerprint;
use MeRezaRezaei\Teleframe\Passport\SessionIdentityResolver;
use PHPUnit\Framework\TestCase;

/**
 * PassportIdentityIntakeTest — THE RE-PLUMB, end to end, against a REAL-SHAPED
 * Bot API response.
 *
 * The defect this file exists for: the package read the verified email/phone
 * from `secure_data.email.address`, `secure_data.phone.phone_number` and the
 * decrypted blob's own top-level `email`/`phone`. Per §5 of
 * `docs/reference/telegram-passport-official.md` the `secure_data` keys are
 * the document types plus `personal_details` and the `Credentials` JSON is
 * `{secure_data, nonce}`, so NONE of those three coordinates exists in
 * Telegram's protocol; per §6 the verified values arrive as PLAINTEXT on the
 * `EncryptedPassportElement` entries in `PassportData.data[]` (an element
 * typed `email` / `phone_number`), already verified. A genuine Telegram
 * response therefore resolved as "no identity", and email/phone login could
 * not work against real Telegram at all.
 *
 * THE COORDINATE IS PINNED HERE, NOT ASSUMED. An intermediate revision of this
 * re-plumb read `PassportData.email` / `PassportData.phone_number` — "top-level
 * siblings of credentials" — on a prose reading of §6. `PassportData` has
 * exactly TWO fields, `data` and `credentials`; those two names are fields of
 * an ELEMENT, not of the object. That revision was green here because the
 * fixtures were written to match it, which is the same failure
 * `SpecCredentialFactory` exists to prevent. So this file builds real
 * `PassportData` objects through `PassportElementFactory`, which cannot
 * express the invented shape, and `test_an_email_beside_the_credentials_is_not_read`
 * asserts the invented shape yields no claim.
 *
 * THE POLARITY RULE FOR THIS FILE, inherited from `SpecCredentialFactory`: the
 * fixture is built from the specification, and where the spec and the
 * implementation disagree the FIXTURE IS RIGHT. Every update below is a real
 * Bot API `Update` with a real `passport_data` object; the old shapes are kept
 * only as negatives, and they must be REFUSED — loudly, with no lookup issued
 * and nothing bound.
 *
 * The nonce rules are pinned here too, stated once for both flows, because the
 * two flows use DIFFERENT mechanisms on purpose and that difference used to be
 * undocumented:
 *
 *   ATTACH  (`PassportLoginService`): the decrypted `Credentials.nonce` is
 *           consumed through `NonceRegistry`, a ONE-TIME KEYED LOOKUP — the
 *           registry maps a keyed digest of the nonce to the userId it was
 *           issued for, and `consume()` deletes before it answers. There is no
 *           byte comparison at all: an unknown nonce is simply absent, which
 *           is the single-use property. Single-use ✓, expiring ✓, CSPRNG ✓.
 *
 *   ANONYMOUS (`PassportAnonymousLoginService`): the nonce is compared in
 *           CONSTANT TIME over `sha256(nonce)` DIGESTS
 *           (`SessionFingerprint::matches`) against the digest the pending
 *           marker recorded, and the marker itself is consumed ONE-TIME
 *           before that comparison runs. So: digest comparison ✓, single-use
 *           ✓ — the two halves are the registry and the digest.
 *
 * Neither is bypassable: on the attach flow the registry is the only source of
 * the binding userId and it is destroyed on first use; on the anonymous flow
 * the marker is destroyed before the digest is even compared, so a wrong
 * browser cannot even spend-and-retry, and no caller can pass an unchecked
 * value because both parameters are non-nullable.
 */
final class PassportIdentityIntakeTest extends TestCase
{
    private string $pemFile = '';

    private string $publicPem = '';

    private string $privatePem = '';

    private SpecCredentialFactory $factory;

    protected function setUp(): void
    {
        IntakeUser::$resolved = null;
        IntakeUser::$lookups = [];

        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        self::assertNotFalse($key);
        self::assertTrue(openssl_pkey_export($key, $this->privatePem));
        $details = openssl_pkey_get_details($key);
        self::assertIsArray($details);

        $this->publicPem = (string) $details['key'];
        $this->factory = SpecCredentialFactory::withKeys($this->privatePem, $this->publicPem);

        $this->pemFile = (string) tempnam(sys_get_temp_dir(), 'teleframe-passport-intake-');
        self::assertNotSame('', $this->pemFile);
        file_put_contents($this->pemFile, $this->privatePem);
        chmod($this->pemFile, 0o600);
    }

    protected function tearDown(): void
    {
        if ($this->pemFile !== '' && is_file($this->pemFile)) {
            unlink($this->pemFile);
        }
        IntakeUser::$resolved = null;
        IntakeUser::$lookups = [];
    }

    // ── the intake DTO ───────────────────────────────────────────────

    public function test_the_intake_reads_the_verified_claims_off_the_element_list(): void
    {
        $credentials = EncryptedCredentials::fromUpdate($this->update(
            $this->seal(['secure_data' => [], 'nonce' => 'n-1']),
            ['email' => 'ada@example.test', 'phone_number' => '+15551234567'],
        ));

        self::assertNotNull($credentials);
        self::assertSame('ada@example.test', $credentials->claims->email());
        self::assertSame('+15551234567', $credentials->claims->phone());
        self::assertSame(['email', 'phone'], $credentials->claims->types());
    }

    /**
     * THE COORDINATE IS PINNED, INCLUDING THE INVENTED ONE. A `PassportData`
     * has exactly two fields — `data` and `credentials` — so an `email` or a
     * `phone_number` placed BESIDE `credentials` is a coordinate Telegram never
     * writes. Reading it would be the same class of defect as the
     * `secure_data.email` this whole re-plumb removed: green against a
     * fixture, unreachable against real Telegram. So the invented shape must
     * yield NO claim.
     */
    public function test_an_email_beside_the_credentials_is_not_read(): void
    {
        // Hand-built, deliberately NOT via the factory: the factory cannot
        // express this shape, which is what makes this assertion possible.
        $update = [
            'update_id' => 1,
            'message' => [
                'from' => ['id' => 4242],
                'passport_data' => [
                    'credentials' => $this->seal(['secure_data' => [], 'nonce' => 'n-1']),
                    'email' => 'ada@example.test',
                    'phone_number' => '+15551234567',
                ],
            ],
        ];

        $credentials = EncryptedCredentials::fromUpdate($update);

        self::assertNotNull($credentials);
        self::assertNull($credentials->claims->email(), 'PassportData has no top-level email field');
        self::assertNull($credentials->claims->phone(), 'PassportData has no top-level phone_number field');
    }

    /**
     * An element's `type` is what selects the field. A value sitting on an
     * element of some OTHER type is not this package's claim — otherwise an
     * attacker who can influence one element's contents could get a claim read
     * out of a coordinate Telegram does not vouch for.
     */
    public function test_a_value_on_an_element_of_another_type_is_not_read(): void
    {
        $claims = PassportVerifiedClaims::fromPassportData([
            'data' => [
                ['type' => 'personal_details', 'email' => 'ada@example.test'],
                ['type' => 'passport', 'phone_number' => '+15551234567'],
            ],
        ]);

        self::assertNull($claims->email());
        self::assertNull($claims->phone());
    }

    /**
     * A value inside an element's ENCRYPTED `data` sub-object is exactly the
     * non-spec read this class exists to remove, so the reader does not
     * descend into it even when the element is correctly typed.
     */
    public function test_an_encrypted_element_data_sub_object_is_not_descended_into(): void
    {
        $claims = PassportVerifiedClaims::fromPassportData([
            'data' => [
                ['type' => 'email', 'data' => ['email' => 'ada@example.test']],
                ['type' => 'phone_number', 'data' => ['phone_number' => '+15551234567']],
            ],
        ]);

        self::assertNull($claims->email());
        self::assertNull($claims->phone());
    }

    /**
     * Telegram sends at most one element of each claim type, so two that
     * DISAGREE are a protocol violation. The claim is dropped rather than
     * resolved to a winner: picking one would let whoever can append a second
     * element choose which identity a login resolves.
     */
    public function test_two_elements_of_the_same_claim_type_that_disagree_yield_no_claim(): void
    {
        $claims = PassportVerifiedClaims::fromPassportData([
            'data' => [
                ['type' => 'email', 'email' => 'attacker@example.test'],
                ['type' => 'email', 'email' => 'victim@example.test'],
                ['type' => 'phone_number', 'phone_number' => '+15551234567'],
            ],
        ]);

        self::assertNull($claims->email(), 'a self-contradicting payload names no identity');
        self::assertSame(
            '+15551234567',
            $claims->phone(),
            'and the uncontested claim is unaffected',
        );
    }

    /**
     * The same, for the phone. Kept as its own test rather than folded into
     * the email one on purpose: the two claims are separate branches, and a
     * mutation run confirmed the email conflict is pinned while the phone one
     * was not — a conflict check that exists for email and not for phone is
     * the kind of asymmetry that only shows up under mutation.
     */
    public function test_two_elements_of_the_same_phone_type_that_disagree_yield_no_claim(): void
    {
        $claims = PassportVerifiedClaims::fromPassportData([
            'data' => [
                ['type' => 'phone_number', 'phone_number' => '+15550000001'],
                ['type' => 'phone_number', 'phone_number' => '+15550000002'],
                ['type' => 'email', 'email' => 'ada@example.test'],
            ],
        ]);

        self::assertNull($claims->phone(), 'a self-contradicting phone names no identity');
        self::assertSame('ada@example.test', $claims->email(), 'and the uncontested claim is unaffected');
    }

    /**
     * Two AGREEING elements are not a conflict, so the claim survives — a
     * duplicated-but-identical element must not lock a legitimate user out.
     */
    public function test_two_identical_elements_of_the_same_claim_type_agree(): void
    {
        $claims = PassportVerifiedClaims::fromPassportData([
            'data' => [
                ['type' => 'email', 'email' => 'ada@example.test'],
                ['type' => 'email', 'email' => 'ada@example.test'],
            ],
        ]);

        self::assertSame('ada@example.test', $claims->email());
    }

    public function test_an_mtproto_shaped_update_carries_no_claims(): void
    {
        // The MTProto surface has no `PassportData` object: its verified
        // values live on a `values: Vector<SecureValue>` as `plain_data`
        // (§6), which is part of the SecureValue traversal this package
        // documents as ABSENT. So the intake yields no claim — and every
        // claim-based login on this shape is refused rather than sourced from
        // a location Telegram did not use. See PassportVerifiedClaims.
        $update = [
            'update_id' => 1,
            '_' => 'updateNewMessage',
            'data' => 'raw-bytes',
            'hash' => 'hash',
            'secret' => 'raw-secret',
            'values' => [
                ['_' => 'secureValueTypeEmail', 'plain_data' => ['_' => 'securePlainEmail', 'email' => 'ada@example.test']],
            ],
        ];

        $credentials = EncryptedCredentials::fromUpdate($update);

        self::assertNotNull($credentials);
        self::assertNull($credentials->claims->email());
        self::assertNull($credentials->claims->phone());
    }

    public function test_a_non_string_or_blank_claim_is_treated_as_absent(): void
    {
        self::assertNull(
            PassportVerifiedClaims::fromPassportData(['data' => [['type' => 'email', 'email' => ['nope']]]])->email()
        );
        self::assertNull(
            PassportVerifiedClaims::fromPassportData(['data' => [['type' => 'email', 'email' => '   ']]])->email()
        );
        self::assertNull(
            PassportVerifiedClaims::fromPassportData(['data' => [['type' => 'phone_number', 'phone_number' => 42]]])->phone()
        );
        self::assertNull(PassportVerifiedClaims::fromPassportData([])->email());
        self::assertNull(PassportVerifiedClaims::fromPassportData(['data' => 'not-a-list'])->email());
    }

    // ── THE POSITIVE: a real-shaped response resolves from §6 ──────────

    public function test_a_real_shaped_response_resolves_the_identity_from_the_plaintext(): void
    {
        $nonces = new MemoryNonceRegistry(900);
        $writer = new MemoryBindingWriter;
        $service = $this->attachService($nonces, $writer);
        $nonce = $nonces->issue(42);
        IntakeUser::$resolved = IntakeUser::forId(42);

        // A REAL Bot API `PassportData`: the encrypted credentials, a §5
        // `personal_details` document element, and the verified email as the
        // PLAINTEXT `email` field of an element typed `email` — all in
        // `data[]`, beside the credentials, never inside secure_data.
        $update = $this->update(
            $this->seal([
                'secure_data' => ['personal_details' => ['first_name' => 'Ada', 'last_name' => 'Lovelace']],
                'nonce' => $nonce,
            ]),
            ['email' => 'ada@example.test'],
            fromId: 1724372757,
        );

        $result = $service->login($update, 42);

        self::assertInstanceOf(LoginResult::class, $result);
        self::assertSame(1724372757, $result->tgUserId);
        self::assertSame(42, (int) $result->user->getKey());
        self::assertSame([['key' => 'email', 'value' => 'ada@example.test']], IntakeUser::$lookups, 'the PLAINTEXT is what was looked up');
        self::assertSame(['personal_details', 'email'], $result->fieldsVerified);
        self::assertCount(1, $writer->rows());
    }

    public function test_a_real_shaped_phone_response_resolves_through_the_phone_column(): void
    {
        $nonces = new MemoryNonceRegistry(900);
        $service = $this->attachService($nonces);
        $nonce = $nonces->issue(42);
        IntakeUser::$resolved = IntakeUser::forId(42);

        $result = $service->login($this->update(
            $this->seal(['secure_data' => ['personal_details' => ['first_name' => 'Ada']], 'nonce' => $nonce]),
            ['phone_number' => '+15551234567'],
        ), 42);

        self::assertInstanceOf(LoginResult::class, $result);
        self::assertSame([['key' => 'phone', 'value' => '+15551234567']], IntakeUser::$lookups);
        self::assertSame(['personal_details', 'phone'], $result->fieldsVerified);
    }

    // ── THE NEGATIVES: the old shape is REFUSED, not quietly accepted ─

    /**
     * THE REGRESSION TEST FOR THE WHOLE DEFECT. The payload carries an email
     * ONLY as `secure_data.email.address` — the coordinate the package used to
     * read and the one §5 says does not exist. It must be REFUSED outright by
     * the validator (the key is not a SecureData type), long before any
     * identity lookup, and it must not bind anything.
     *
     * Before the re-plumb this exact payload produced a successful login.
     */
    public function test_a_payload_whose_only_email_is_inside_secure_data_is_refused(): void
    {
        $nonces = new MemoryNonceRegistry(900);
        $writer = new MemoryBindingWriter;
        // The SHIPPED allow-list, which still contains `email` — so this is
        // refused by the §5 SHAPE gate and not by configuration.
        $service = $this->attachService($nonces, $writer, config: [
            'allowed_types' => PassportPayloadTypes::SHIPPED,
        ]);
        $nonce = $nonces->issue(42);
        IntakeUser::$resolved = IntakeUser::forId(42);

        $update = $this->update(
            $this->seal([
                'secure_data' => [
                    'personal_details' => ['first_name' => 'Ada'],
                    'email' => ['address' => 'ada@example.test'],
                ],
                'nonce' => $nonce,
            ]),
        );

        try {
            $service->login($update, 42);
            self::fail('a secure_data.email is not a Telegram field and must be refused');
        } catch (PassportLoginException $e) {
            self::assertStringContainsString('not a SecureData type Telegram sends', $e->getMessage());
            self::assertStringNotContainsString('ada@example.test', $e->getMessage());
        }

        self::assertSame([], IntakeUser::$lookups, 'and no lookup is ever issued');
        self::assertSame([], $writer->rows(), 'and nothing is bound');
    }

    /**
     * The same for the phone coordinate, and for the DEFAULT host
     * configuration: the shipped `allowed_types` still lists `email`/`phone`
     * (they are the users-table column names, and the value's provenance
     * allow-list), so this proves the §5 shape check is not overridable by
     * configuration.
     */
    public function test_a_payload_whose_only_phone_is_inside_secure_data_is_refused(): void
    {
        $nonces = new MemoryNonceRegistry(900);
        $service = $this->attachService($nonces, config: [
            'allowed_types' => PassportPayloadTypes::SHIPPED,
        ]);
        $nonce = $nonces->issue(42);
        IntakeUser::$resolved = IntakeUser::forId(42);

        $this->expectException(PassportLoginException::class);
        $this->expectExceptionMessage('not a SecureData type Telegram sends');

        $service->login($this->update($this->seal([
            'secure_data' => ['phone' => ['phone_number' => '+15551234567']],
            'nonce' => $nonce,
        ])), 42);
    }

    /**
     * THE SAME ATTACK UNDER THE SHIPPED DEFAULTS, and the one that matters.
     *
     * `teleframe.passport.require_verified` ships FALSE. The two refusals
     * above run with it ON, which means a second layer (the verification gate)
     * also catches the payload — so on their own they would still pass against
     * a build that had re-opened the `secure_data` read but happened to keep
     * the gate. This test removes that second layer: shipped allow-list
     * (contains `email`) and `require_verified` off, i.e. exactly what a host
     * gets out of the box.
     *
     * Against the pre-fix build this payload produced a SUCCESSFUL LOGIN as
     * whoever the forged `secure_data.email.address` named — an account
     * takeover from a coordinate Telegram never writes, forgeable by anyone
     * who can get credentials decrypted with the service's own public key.
     */
    public function test_a_secure_data_email_cannot_take_over_an_account_under_shipped_defaults(): void
    {
        $nonces = new MemoryNonceRegistry(900);
        $writer = new MemoryBindingWriter;
        $service = $this->attachService($nonces, $writer, config: [
            'allowed_types' => PassportPayloadTypes::SHIPPED,
            'require_verified' => false,
        ]);
        $nonce = $nonces->issue(42);
        IntakeUser::$resolved = IntakeUser::forId(42);

        $update = $this->update($this->seal([
            'secure_data' => [
                'personal_details' => ['first_name' => 'Ada'],
                'email' => ['address' => 'victim@example.test'],
            ],
            'nonce' => $nonce,
        ]));

        try {
            $service->login($update, 42);
            self::fail('a secure_data.email must never become an identity, even with every gate off');
        } catch (PassportLoginException $e) {
            self::assertStringNotContainsString('victim@example.test', $e->getMessage());
        }

        self::assertSame([], IntakeUser::$lookups, 'the users table is never consulted for it');
        self::assertSame([], $writer->rows(), 'and nothing is bound');
    }

    /**
     * And the third non-spec coordinate: a bare top-level `email` inside the
     * encrypted blob (§5 says the `Credentials` JSON is `{secure_data, nonce}`).
     * It is never read, so a field-mode login has no claim, and the chain
     * refuses unresolved — loudly, with no lookup and no binding.
     */
    public function test_a_payload_whose_only_email_is_a_blob_top_level_scalar_is_refused(): void
    {
        $nonces = new MemoryNonceRegistry(900);
        $writer = new MemoryBindingWriter;
        // The verification gate OFF, so this exercises the RESOLVER's refusal
        // rather than the gate's earlier one — the two are pinned separately.
        $service = $this->attachService($nonces, $writer, config: ['require_verified' => false]);
        $nonce = $nonces->issue(42);
        IntakeUser::$resolved = IntakeUser::forId(42);

        try {
            $service->login($this->update($this->seal([
                'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
                'nonce' => $nonce,
                'email' => 'ada@example.test',
            ])), 42);
            self::fail('a non-spec coordinate must not become an identity');
        } catch (PassportLoginException $e) {
            self::assertStringContainsString('unresolved', $e->getMessage());
            self::assertStringNotContainsString('ada@example.test', $e->getMessage());
        }

        self::assertSame([], IntakeUser::$lookups);
        self::assertSame([], $writer->rows());
    }

    // ── secure_data is still parsed for what it ACTUALLY holds (§5) ────

    public function test_the_secure_data_document_types_are_still_parsed_for_what_they_are(): void
    {
        $nonces = new MemoryNonceRegistry(900);
        $service = $this->attachService(
            $nonces,
            config: ['user_lookup' => 'session', 'require_verified' => false],
            fieldMode: false,
        );
        $nonce = $nonces->issue(42);
        IntakeUser::$resolved = IntakeUser::forId(42);

        // Every §5 key the host allows, present at once, with `personal_details`
        // carrying the one plain scalar this package models. No claim at all —
        // and the strictest FIELD configuration is switched off for this one
        // test on purpose: a document-only response has no claim, so field
        // mode with require_verified refuses it (see the gate's own tests).
        // Session mode is the shipped default and the documents are recorded
        // as evidence.
        $result = $service->login($this->update($this->seal([
            'secure_data' => [
                'personal_details' => ['first_name' => 'Ada', 'last_name' => 'Lovelace', 'birth_date' => '10.12.1815'],
                'passport' => ['type' => 'passport'],
                'internal_passport' => ['type' => 'internal_passport'],
                'driver_license' => ['type' => 'driver_license'],
                'identity_card' => ['type' => 'identity_card'],
                'address' => ['type' => 'address'],
                'utility_bill' => ['type' => 'utility_bill'],
                'bank_statement' => ['type' => 'bank_statement'],
                'rental_agreement' => ['type' => 'rental_agreement'],
                'passport_registration' => ['type' => 'passport_registration'],
                'temporary_registration' => ['type' => 'temporary_registration'],
            ],
            'nonce' => $nonce,
        ])), 42);

        self::assertInstanceOf(LoginResult::class, $result);
        self::assertSame(PassportPayloadTypes::ALL, $result->fieldsVerified);
        self::assertSame(
            [['key' => 'id', 'value' => 42]],
            IntakeUser::$lookups,
            'no claim, so the users table is consulted by the bound SESSION id only',
        );
    }

    // ── the nonce, pinned on BOTH flows ───────────────────────────────

    public function test_the_attach_flow_refuses_a_nonce_it_never_issued(): void
    {
        $nonces = new MemoryNonceRegistry(900);
        $writer = new MemoryBindingWriter;
        $service = $this->attachService($nonces, $writer);
        IntakeUser::$resolved = IntakeUser::forId(42);

        $this->expectException(PassportLoginException::class);
        $this->expectExceptionMessage('unknown or expired passport nonce');

        // The payload's own `Credentials.nonce` is well-formed and is even
        // what Telegram returned; it simply is not one this host issued. The
        // one-time registry lookup is the whole mechanism — there is no byte
        // comparison to get wrong.
        $service->login($this->update($this->seal([
            'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
            'nonce' => 'a-nonce-this-host-never-issued',
        ]), ['email' => 'ada@example.test']), 42);
    }

    public function test_the_attach_flow_nonce_is_single_use(): void
    {
        $nonces = new MemoryNonceRegistry(900);
        $writer = new MemoryBindingWriter;
        $service = $this->attachService($nonces, $writer);
        $nonce = $nonces->issue(42);
        IntakeUser::$resolved = IntakeUser::forId(42);

        $update = $this->update(
            $this->seal(['secure_data' => ['personal_details' => ['first_name' => 'Ada']], 'nonce' => $nonce]),
            ['email' => 'ada@example.test'],
        );

        self::assertInstanceOf(LoginResult::class, $service->login($update, 42));

        // Second presentation of the SAME credentials: refused by the registry,
        // and no second binding.
        try {
            $service->login($update, 42);
            self::fail('a spent nonce must be refused');
        } catch (PassportLoginException $e) {
            self::assertStringContainsString('unknown or expired passport nonce', $e->getMessage());
        }

        self::assertCount(1, $writer->rows());
    }

    public function test_the_anonymous_flow_refuses_a_nonce_minted_for_another_trip(): void
    {
        [$service, $pending] = $this->anonymousService();
        $ticket = $pending->issue(SessionFingerprint::of('session-a'));

        $this->expectException(PassportLoginException::class);
        $this->expectExceptionMessage('this login flow');

        // Constant-time comparison of sha256 DIGESTS against the digest the
        // marker recorded — the credentials were produced for a different trip.
        $service->login(
            $this->update($this->seal([
                'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
                'nonce' => 'a-nonce-from-another-flow',
            ]), ['email' => 'ada@example.test']),
            $ticket->token(),
            SessionFingerprint::of('session-a'),
        );
    }

    public function test_the_anonymous_flow_nonce_and_flow_are_both_single_use(): void
    {
        [$service, $pending] = $this->anonymousService();
        $ticket = $pending->issue(SessionFingerprint::of('session-a'));

        $update = $this->update(
            $this->seal(['secure_data' => ['personal_details' => ['first_name' => 'Ada']], 'nonce' => $ticket->nonce()]),
            ['email' => 'ada@example.test'],
        );

        // First presentation: the credentials, marker, fingerprint and nonce
        // all agree, so the chain gets as far as the user lookup (disabled
        // here) and refuses THERE — which proves everything before it passed.
        try {
            $service->login($update, $ticket->token(), SessionFingerprint::of('session-a'));
            self::fail('no user matches here, so the chain must refuse');
        } catch (PassportLoginException $e) {
            self::assertStringContainsString('no Laravel user matches', $e->getMessage());
        }

        // Second presentation of the same credentials: the MARKER is gone, so
        // the replay never even reaches the digest comparison.
        $this->expectException(PassportLoginException::class);
        $this->expectExceptionMessage('cannot be replayed');

        $service->login($update, $ticket->token(), SessionFingerprint::of('session-a'));
    }

    public function test_the_anonymous_flow_logs_in_from_a_real_shaped_response(): void
    {
        [$service, $pending] = $this->anonymousService(withLookup: true);
        $ticket = $pending->issue(SessionFingerprint::of('session-a'));
        IntakeUser::$resolved = IntakeUser::forId(42);

        $result = $service->login(
            $this->update(
                $this->seal([
                    'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
                    'nonce' => $ticket->nonce(),
                ]),
                ['email' => 'ada@example.test'],
            ),
            $ticket->token(),
            SessionFingerprint::of('session-a'),
        );

        self::assertInstanceOf(LoginResult::class, $result);
        self::assertSame(42, (int) $result->user->getKey());
        self::assertSame([['key' => 'email', 'value' => 'ada@example.test']], IntakeUser::$lookups);
    }

    /**
     * The other half of the test above, and the reason it now configures a bot:
     * a host holding a Passport RSA key but NO bot behind it runs a login
     * endpoint whose entire premise is absent — the bot private key is what
     * decrypts the envelope and the bot token is what the per-element hash is
     * taken over — so the chain refuses it at the gate rather than serving it.
     *
     * Asserted on SUBSTANCE, not on a sentence: the refusal must happen, and
     * the message must NAME the missing config key, so a rewording of the
     * explanation does not turn this into a brittle string test.
     */
    public function test_the_anonymous_flow_refuses_a_host_with_no_bot_behind_it(): void
    {
        [$service, $pending] = $this->anonymousService(withLookup: true, withBot: false);
        $ticket = $pending->issue(SessionFingerprint::of('session-a'));
        IntakeUser::$resolved = IntakeUser::forId(42);

        $update = $this->update(
            $this->seal([
                'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
                'nonce' => $ticket->nonce(),
            ]),
            ['email' => 'ada@example.test'],
        );

        $refused = false;
        try {
            $service->login($update, $ticket->token(), SessionFingerprint::of('session-a'));
        } catch (PassportLoginException $e) {
            $refused = true;
            // The KEY is named, not the prose around it.
            self::assertStringContainsString('teleframe.passport.bot_id', $e->getMessage());
            self::assertStringContainsString('teleframe.passport.bot_username', $e->getMessage());
            // A bot-less host is refused for THAT reason and nothing else.
            self::assertStringNotContainsString('no Laravel user matches', $e->getMessage());
        }

        self::assertTrue($refused, 'a bot-less passport host must never be served');

        // Refused AT the gate: the marker was never consumed, so a refused
        // host does not spend the user's flow token.
        self::assertNotNull($pending->consume($ticket->token()));
        self::assertSame([], IntakeUser::$lookups, 'the users table is never reached');
    }

    public function test_the_anonymous_flow_refuses_the_old_secure_data_shape(): void
    {
        [$service, $pending] = $this->anonymousService();
        $ticket = $pending->issue(SessionFingerprint::of('session-a'));
        IntakeUser::$resolved = IntakeUser::forId(42);

        $this->expectException(PassportLoginException::class);
        $this->expectExceptionMessage('not a SecureData type Telegram sends');

        $service->login(
            $this->update($this->seal([
                'secure_data' => ['email' => ['address' => 'ada@example.test']],
                'nonce' => $ticket->nonce(),
            ])),
            $ticket->token(),
            SessionFingerprint::of('session-a'),
        );

        self::assertSame([], IntakeUser::$lookups);
    }

    // ── helpers ───────────────────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $payload
     * @return array{data: string, hash: string, secret: string}
     */
    private function seal(array $payload): array
    {
        return $this->factory->seal($payload);
    }

    /**
     * A REAL Bot API update. `message.passport_data` is a real `PassportData`
     * object: the `data` element list (which is where the verified email and
     * phone live, §6) plus the `credentials` envelope. Those are its ONLY
     * two fields — the factory cannot put an `email` beside `credentials`,
     * because Telegram never sends one there.
     *
     * @param  array{data: string, hash: string, secret: string}  $envelope
     * @param  array<string, mixed>  $claims  verified claims / document elements
     * @return array<string, mixed>
     */
    private function update(array $envelope, array $claims = [], int $fromId = 4242): array
    {
        return [
            'update_id' => 1,
            'message' => [
                'from' => ['id' => $fromId],
                'passport_data' => PassportElementFactory::passportData($envelope, $claims),
            ],
        ];
    }

    /**
     * The claim-matching (field) seam with `require_verified` on — the
     * strictest shipped configuration, and the one every refusal test below
     * runs under unless it says otherwise.
     *
     * @param  array<string, mixed>  $config
     */
    private function attachService(
        MemoryNonceRegistry $nonces,
        ?MemoryBindingWriter $writer = null,
        array $config = [],
        bool $fieldMode = true,
    ): PassportLoginService {
        return new PassportLoginService(
            config: new PassportConfig(array_merge([
                'enabled' => true,
                'private_key_path' => $this->pemFile,
                'account_id' => 7,
                // Claims included: this suite drives field-mode identity, which
                // resolves FROM a verified email/phone claim, so a host that
                // excluded those types would (correctly) refuse its own fixture.
                'allowed_types' => PassportPayloadTypes::ALL_WITH_CLAIMS,
                'user_lookup' => 'field',
                'require_verified' => true,
            ], $config)),
            keys: new PassportKeyLoader($this->pemFile),
            nonces: $nonces,
            // The seam is INJECTED, so `user_lookup` in the config array above
            // is documentation here, not a switch: the caller picks the
            // resolver. (The provider does that wiring; this suite bypasses it
            // deliberately, so it wires the same two seams by hand.)
            identity: $fieldMode
                ? new FieldIdentityResolver(
                    userClass: IntakeUser::class,
                    emailColumn: 'email',
                    phoneColumn: 'phone',
                )
                : new SessionIdentityResolver(IntakeUser::class),
            writer: $writer ?? new MemoryBindingWriter,
        );
    }

    /**
     * @return array{0: PassportAnonymousLoginService, 1: MemoryPendingLoginRegistry}
     */
    private function anonymousService(bool $withLookup = false, bool $withBot = true): array
    {
        $config = [
            'enabled' => true,
            'anonymous_login' => true,
            'private_key_path' => $this->pemFile,
            'account_id' => 7,
            // The anonymous chain resolves its identity field from the §6
            // claim, so the host's policy has to permit that claim type.
            'allowed_types' => PassportPayloadTypes::ALL_WITH_CLAIMS,
        ];

        // Passport is a BOT API feature, so the chain REFUSES a host that has
        // the key but no bot BEFORE the marker is spent. A fixture that means
        // to reach any check past that gate therefore has to configure a bot;
        // the one test that means to reach the gate itself asks for none.
        if ($withBot) {
            $config['bot_id'] = 7_700_000_01;
            $config['bot_username'] = 'teleframe_intake_test_bot';
        }

        $pending = new MemoryPendingLoginRegistry(900);

        return [
            new PassportAnonymousLoginService(
                config: new PassportConfig($config),
                keys: new PassportKeyLoader($this->pemFile),
                pending: $pending,
                writer: new MemoryBindingWriter,
                // The lookup is DISABLED by default on purpose, so a test can
                // drive the chain all the way to the identity step and prove
                // that every earlier check passed. A test that needs a user
                // resolved asks for the seam explicitly.
                userClass: '',
                finder: $withLookup
                    ? static function (string $column, string $value): ?Model {
                        IntakeUser::$lookups[] = ['key' => $column, 'value' => $value];

                        return IntakeUser::$resolved;
                    }
                : null,
            ),
            $pending,
        ];
    }
}

/**
 * The §5 `secure_data` key catalogue, spelled out here so this suite does not
 * depend on the implementation's copy of the list (a fixture that reads the
 * constant it is testing cannot fail).
 */
final class PassportPayloadTypes
{
    /**
     * The SHIPPED default `teleframe.passport.allowed_types`, verbatim from
     * `src/Laravel/config/passport.php`. It lists `email` and `phone` even
     * though §5 says `secure_data` may not carry them — which is the whole
     * point of pinning it here: a negative test that used a tightened
     * allow-list would be refused by the ALLOW-LIST and would prove nothing
     * about the §5 shape gate. With the shipped list in place, the only thing
     * that can refuse a `secure_data.email` is the gate.
     */
    public const SHIPPED = ['personal_details', 'email', 'phone'];

    public const ALL = [
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
     * `ALL` PLUS the two §6 claim types.
     *
     * NEEDED because `PassportValueValidator` now gates the claim channel on
     * the host's allow-list, exactly as it always gated the `secure_data`
     * types. A service fixture whose `allowed_types` omits `email` while its
     * payload carries a verified email claim is — correctly — refused: the
     * host configured a policy that excludes the very type the login resolves
     * an identity from.
     *
     * `ALL` itself is left as the DOCUMENT list, because it is asserted
     * verbatim as `fieldsVerified` by the document-only response test, and a
     * document-only payload carries no claim. The two constants now say what
     * they mean: "every §5 type" and "every type this host will accept,
     * claims included".
     */
    public const ALL_WITH_CLAIMS = [
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
        'email',
        'phone',
    ];
}

/**
 * A DB-free Eloquent Model the resolvers can hand back, recording every lookup
 * so a test can assert whether the users table was consulted at all.
 */
final class IntakeUser extends Model
{
    public ?int $id = 42;

    public static ?self $resolved = null;

    /** @var list<array{key: string, value: mixed}> */
    public static array $lookups = [];

    public static function forId(int $id): self
    {
        $user = new self;
        $user->id = $id;
        $user->setAttribute('id', $id);

        return $user;
    }

    public static function query(): static
    {
        return new self;
    }

    public function where(string $key, mixed $value): static
    {
        self::$lookups[] = ['key' => $key, 'value' => $value];

        return $this;
    }

    public function first(): ?static
    {
        return self::$resolved;
    }
}
