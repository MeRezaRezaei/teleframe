<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Passport;

use Illuminate\Database\Eloquent\Model;
use MeRezaRezaei\Teleframe\Passport\FieldIdentityResolver;
use MeRezaRezaei\Teleframe\Passport\LoginResult;
use MeRezaRezaei\Teleframe\Passport\MemoryBindingWriter;
use MeRezaRezaei\Teleframe\Passport\MemoryNonceRegistry;
use MeRezaRezaei\Teleframe\Passport\PassportConfig;
use MeRezaRezaei\Teleframe\Passport\PassportKeyLoader;
use MeRezaRezaei\Teleframe\Passport\PassportLoginException;
use MeRezaRezaei\Teleframe\Passport\PassportLoginService;
use MeRezaRezaei\Teleframe\Passport\SessionIdentityResolver;
use PHPUnit\Framework\TestCase;

/**
 * PassportRequireVerifiedTest — `require_verified` must be a CHECK, not a
 * promise (2026-09-26 security gap 1).
 *
 * THE GAP. The toggle was read in exactly one place: the provider, which threw
 * when `user_lookup=field` was configured without it. Nothing on the login
 * path read it, and `PassportConfig` had no accessor for it at all — so a
 * caller could pass the default `session` mode and log in with a payload
 * carrying zero verified fields, and any caller handing a payload straight to
 * `FieldIdentityResolver` bypassed the acknowledgement completely.
 *
 * THE RULE UNDER TEST, as implemented BEFORE the 2026-09-26 re-plumb:
 *   refuse ⟺ require_verified is true
 *           AND the identity seam is claim-matching (a FieldIdentityResolver)
 *           AND the payload carries an email/phone that Telegram did NOT list
 *               in its vetted `secure_data`.
 *
 * AND WHY THAT RULE WAS WRONG, which is the reason this file moved. The third
 * clause described the pre-re-plumb intake, where the only readable email was
 * a `secure_data.email` entry. Per §5 of the reference there is no such
 * `secure_data` key: the verified values are the Bot API `PassportData`
 * element plaintext in `PassportData.data[]` (§6). Under the old rule every REAL Telegram response
 * was refused, so the flag was a lockout rather than a check — and the old
 * `secure_data` shape it did admit was a shape Telegram never sends.
 *
 * THE RULE AS IT STANDS:
 *   refuse ⟺ require_verified is true
 *           AND the identity seam is claim-matching (a FieldIdentityResolver)
 *           AND the response carries NO Telegram-verified field at all.
 *
 * A claim Telegram vouched for (the §6 plaintext) satisfies the host's demand
 * by definition; a claim at a non-spec location cannot become one, because
 * `PassportPayload` no longer reads it and `PassportValueValidator` refuses a
 * `secure_data.email` outright. The refusal is still raised BEFORE the users
 * table is touched, and the message still names TYPES only.
 */
final class PassportRequireVerifiedTest extends TestCase
{
    private string $pemFile = '';

    private string $publicPem = '';

    private string $privatePem = '';

    protected function setUp(): void
    {
        VerificationUser::$resolved = null;
        VerificationUser::$lookups = [];

        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        self::assertNotFalse($key);
        openssl_pkey_export($key, $this->privatePem);
        $details = openssl_pkey_get_details($key);
        self::assertIsArray($details);

        $this->pemFile = tempnam(sys_get_temp_dir(), 'teleframe-passport-verified-');
        self::assertNotFalse($this->pemFile);
        file_put_contents($this->pemFile, $this->privatePem);
        chmod($this->pemFile, 0o600);

        $this->publicPem = $details['key'];
    }

    protected function tearDown(): void
    {
        if ($this->pemFile !== '' && is_file($this->pemFile)) {
            unlink($this->pemFile);
        }
        VerificationUser::$resolved = null;
        VerificationUser::$lookups = [];
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function service(
        array $config,
        MemoryNonceRegistry $nonces,
        bool $fieldMode = false,
        ?MemoryBindingWriter $writer = null,
    ): PassportLoginService {
        $identity = $fieldMode
            ? new FieldIdentityResolver(
                userClass: VerificationUser::class,
                emailColumn: 'email',
                phoneColumn: 'phone',
            )
            : new SessionIdentityResolver(VerificationUser::class);

        return new PassportLoginService(
            config: new PassportConfig(array_merge([
                'enabled' => true,
                'private_key_path' => $this->pemFile,
                'account_id' => 7,
                'allowed_types' => ['personal_details', 'email', 'phone'],
            ], $config)),
            keys: new PassportKeyLoader($this->pemFile),
            nonces: $nonces,
            identity: $identity,
            writer: $writer ?? new MemoryBindingWriter,
        );
    }

    /**
     * Seal a `Credentials` payload with the SPEC-FAITHFUL builder
     * (`SpecCredentialFactory`), against the key this test already generated.
     *
     * The previous body of this method built the fixture to match the
     * IMPLEMENTATION — hash over the unpadded plaintext, no Telegram padding,
     * OpenSSL PKCS#7 on the ciphertext — so it agreed with the code by
     * construction and could not catch a spec violation. The factory is
     * transcribed from upstream's own build order instead, so a drift in
     * either direction is now a test failure.
     *
     * @param  array<string, mixed>  $payload
     * @return array{data: string, secret: string, hash: string}
     */
    private function seal(array $payload): array
    {
        return SpecCredentialFactory::withKeys($this->privatePem, $this->publicPem)->seal($payload);
    }

    /**
     * A REAL Bot API update: the sealed credentials plus, when supplied, the
     * §6 verified plaintext siblings Telegram sends beside them.
     *
     * @param  array{data: string, secret: string, hash: string}  $envelope
     * @param  array<string, string>  $passportData
     * @return array<string, mixed>
     */
    private function updateWith(array $envelope, int $fromId = 4242, array $passportData = []): array
    {
        return [
            'update_id' => 1,
            'message' => [
                'from' => ['id' => $fromId],
                'passport_data' => PassportElementFactory::passportData($envelope, $passportData),
            ],
        ];
    }

    /**
     * A response whose ONLY identity evidence is a bare top-level `email`
     * INSIDE the encrypted blob. Per §5 the `Credentials` JSON is
     * `{secure_data, nonce}`, so this coordinate does not exist; it is the
     * shape the pre-re-plumb implementation read as a claim.
     *
     * @return array<string, mixed>
     */
    private function unverifiedEmailPayload(string $nonce): array
    {
        return [
            'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
            'nonce' => $nonce,
            'email' => 'unverified@example.com',
        ];
    }

    public function test_require_verified_refuses_a_field_mode_login_on_an_unverified_field(): void
    {
        $nonces = new MemoryNonceRegistry(900);
        $service = $this->service(['require_verified' => true], $nonces, fieldMode: true);
        $nonce = $nonces->issue(42);
        VerificationUser::$resolved = VerificationUser::forId(42);

        $this->expectException(PassportLoginException::class);
        $this->expectExceptionMessage('require_verified is enabled');

        $service->login($this->updateWith($this->seal($this->unverifiedEmailPayload($nonce))), 42);
    }

    public function test_the_refusal_names_the_offending_field_type_and_never_its_value(): void
    {
        $nonces = new MemoryNonceRegistry(900);
        $service = $this->service(['require_verified' => true], $nonces, fieldMode: true);
        $nonce = $nonces->issue(42);
        VerificationUser::$resolved = VerificationUser::forId(42);

        try {
            $service->login($this->updateWith($this->seal($this->unverifiedEmailPayload($nonce))), 42);
            self::fail('an unverified claim must be refused when the host demands proof');
        } catch (PassportLoginException $e) {
            self::assertStringContainsString('email', $e->getMessage());
            // The message names the TYPE. The decrypted value must never leak
            // into an exception message (it reaches logs, alerting, tickets).
            self::assertStringNotContainsString('unverified@example.com', $e->getMessage());
        }
    }

    public function test_the_refusal_happens_before_the_users_table_is_touched(): void
    {
        $nonces = new MemoryNonceRegistry(900);
        $service = $this->service(['require_verified' => true], $nonces, fieldMode: true);
        $nonce = $nonces->issue(42);
        VerificationUser::$resolved = VerificationUser::forId(42);

        try {
            $service->login($this->updateWith($this->seal($this->unverifiedEmailPayload($nonce))), 42);
        } catch (PassportLoginException) {
            // expected
        }

        // No lookup was issued: an unverified claim never reaches a query.
        self::assertSame([], VerificationUser::$lookups);
    }

    public function test_the_refusal_leaves_the_binding_and_the_host_hook_untouched(): void
    {
        $nonces = new MemoryNonceRegistry(900);
        $writer = new MemoryBindingWriter;
        $hooked = false;
        $service = new PassportLoginService(
            config: new PassportConfig([
                'enabled' => true,
                'private_key_path' => $this->pemFile,
                'account_id' => 7,
                'allowed_types' => ['personal_details', 'email', 'phone'],
                'require_verified' => true,
            ]),
            keys: new PassportKeyLoader($this->pemFile),
            nonces: $nonces,
            identity: new FieldIdentityResolver(userClass: VerificationUser::class),
            writer: $writer,
            onLogin: static function (Model $user) use (&$hooked): void {
                $hooked = true;
            },
        );
        $nonce = $nonces->issue(42);
        VerificationUser::$resolved = VerificationUser::forId(42);

        try {
            $service->login($this->updateWith($this->seal($this->unverifiedEmailPayload($nonce))), 42);
            self::fail('must be refused');
        } catch (PassportLoginException) {
            // expected
        }

        self::assertSame([], $writer->rows(), 'a refused login must not bind');
        self::assertNull($writer->provenance(4242, 7), 'a refused login must not record provenance');
        self::assertFalse($hooked, 'a refused login must never reach the host auth ride');
    }

    public function test_require_verified_still_admits_a_field_mode_login_on_a_verified_field(): void
    {
        $nonces = new MemoryNonceRegistry(900);
        $service = $this->service(['require_verified' => true], $nonces, fieldMode: true);
        $nonce = $nonces->issue(42);
        VerificationUser::$resolved = VerificationUser::forId(42);

        // §6: Telegram's own verified plaintext `email` ELEMENT. This is
        // what a real response looks like, and it is the only shape that can
        // satisfy a host that demands proof.
        $result = $service->login($this->updateWith(
            $this->seal([
                'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
                'nonce' => $nonce,
            ]),
            passportData: ['email' => 'verified@example.com'],
        ), 42);

        self::assertInstanceOf(LoginResult::class, $result);
        self::assertSame(['personal_details', 'email'], $result->fieldsVerified);
    }

    public function test_require_verified_does_not_break_the_default_session_mode_flow(): void
    {
        // THE REGRESSION THIS GUARDS. Session mode binds to an already
        // authenticated web session via the consumed nonce and never reads a
        // claim from the payload. It must keep working even with the toggle
        // on — and, since the flag defaults to false, with the toggle off too.
        foreach ([false, true] as $requireVerified) {
            $nonces = new MemoryNonceRegistry(900);
            $service = $this->service(['require_verified' => $requireVerified], $nonces);
            $nonce = $nonces->issue(42);
            VerificationUser::$resolved = VerificationUser::forId(42);

            $result = $service->login($this->updateWith($this->seal([
                'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
                'nonce' => $nonce,
            ])), 42);

            self::assertInstanceOf(LoginResult::class, $result);
            self::assertSame(42, $result->user->id);
            self::assertSame(4242, $result->tgUserId);
        }
    }

    public function test_the_default_configuration_admits_a_real_verified_claim(): void
    {
        // require_verified defaults to FALSE, so an ordinary field-mode login
        // on a REAL Telegram response keeps working: the gate is opt-in, never
        // a surprise lockout.
        $nonces = new MemoryNonceRegistry(900);
        $service = $this->service([], $nonces, fieldMode: true);
        $nonce = $nonces->issue(42);
        VerificationUser::$resolved = VerificationUser::forId(42);

        $result = $service->login($this->updateWith(
            $this->seal([
                'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
                'nonce' => $nonce,
            ]),
            passportData: ['email' => 'verified@example.com'],
        ), 42);

        self::assertInstanceOf(LoginResult::class, $result);
    }

    /**
     * THE DEFAULT CONFIGURATION NO LONGER LOGS IN FROM A NON-SPEC LOCATION.
     * This is the defect, restated as a test of the shipped default: before
     * the re-plumb this exact payload produced a `LoginResult` with the gate
     * OFF, because a bare `email` inside the blob was read as a claim. It now
     * resolves nothing and the service throws loud — and with the gate ON the
     * refusal names `require_verified` (the tests above).
     */
    public function test_the_default_configuration_refuses_a_claim_from_a_non_spec_location(): void
    {
        $nonces = new MemoryNonceRegistry(900);
        $service = $this->service([], $nonces, fieldMode: true);
        $nonce = $nonces->issue(42);
        VerificationUser::$resolved = VerificationUser::forId(42);

        $this->expectException(PassportLoginException::class);
        $this->expectExceptionMessage('unresolved');

        $service->login($this->updateWith($this->seal($this->unverifiedEmailPayload($nonce))), 42);
    }

    public function test_a_bare_unverified_phone_is_refused_too(): void
    {
        $nonces = new MemoryNonceRegistry(900);
        $service = $this->service(['require_verified' => true], $nonces, fieldMode: true);
        $nonce = $nonces->issue(42);
        VerificationUser::$resolved = VerificationUser::forId(42);

        $this->expectException(PassportLoginException::class);
        $this->expectExceptionMessage('phone');

        $service->login($this->updateWith($this->seal([
            'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
            'nonce' => $nonce,
            'phone' => '+15551234567',
        ])), 42);
    }

    public function test_the_ambiguity_refusal_survives_the_verification_gate(): void
    {
        // A payload carrying BOTH email and phone stays refused by
        // FieldIdentityResolver. The gate must not have replaced, short-
        // circuited or softened that decision — with the toggle OFF it is the
        // resolver's ambiguity refusal that stops the login, and the user is
        // still unresolved afterwards.
        $nonces = new MemoryNonceRegistry(900);
        $service = $this->service(['require_verified' => false], $nonces, fieldMode: true);
        $nonce = $nonces->issue(42);
        VerificationUser::$resolved = VerificationUser::forId(42);

        $this->expectException(PassportLoginException::class);
        $this->expectExceptionMessage('unresolved');

        $service->login($this->updateWith($this->seal([
            'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
            'nonce' => $nonce,
            'email' => 'a@example.com',
            'phone' => '+15551234567',
        ])), 42);
    }
}

/**
 * The fake Laravel user for the verification gate — same DB-free Model
 * convention as the rest of the Passport suite: query() returns the instance,
 * where()/first() record the lookup and hand back the seeded row, so a test
 * can assert whether the users table was consulted at all.
 */
final class VerificationUser extends Model
{
    public ?int $id = 42;

    public static ?self $resolved = null;

    /** A resolved row as a real users table hands one back: keyed AND identified. */
    public static function forId(int $id): self
    {
        $user = new self;
        $user->id = $id;
        $user->setAttribute('id', $id);

        return $user;
    }

    /** @var list<array{key: string, value: mixed}> */
    public static array $lookups = [];

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
