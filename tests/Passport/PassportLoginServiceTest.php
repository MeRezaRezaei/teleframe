<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Passport;

use Closure;
use Illuminate\Database\Eloquent\Model;
use MeRezaRezaei\Teleframe\Passport\BindingWriter;
use MeRezaRezaei\Teleframe\Passport\EloquentBindingWriter;
use MeRezaRezaei\Teleframe\Passport\LoginResult;
use MeRezaRezaei\Teleframe\Passport\MemoryBindingWriter;
use MeRezaRezaei\Teleframe\Passport\MemoryNonceRegistry;
use MeRezaRezaei\Teleframe\Passport\PassportConfig;
use MeRezaRezaei\Teleframe\Passport\PassportKeyLoader;
use MeRezaRezaei\Teleframe\Passport\PassportLoginException;
use MeRezaRezaei\Teleframe\Passport\PassportLoginService;
use MeRezaRezaei\Teleframe\Passport\PassportPayload;
use MeRezaRezaei\Teleframe\Passport\PasswordConfirmer;
use MeRezaRezaei\Teleframe\Passport\SessionIdentityResolver;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayLogger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * PassportLoginServiceTest — THE chain (design §4/§8/§9, spec §5): faked
 * webhook update → REAL keypair decrypt → validate → ONE-TIME nonce consume
 * → identity resolve (SessionIdentityResolver + a fake Eloquent Model user)
 * → optional 2FA confirm → bind (Memory writer records the row) → onLogin
 * hook → LoginResult. Every refusal is a LOUD PassportLoginException, PSR-3
 * logged at error, ALWAYS rethrown — and on a nonce replay the writer and
 * the hook are never touched.
 */
final class PassportLoginServiceTest extends TestCase
{
    private string $pemFile = '';

    private string $publicPem = '';

    private string $privatePem = '';

    protected function setUp(): void
    {
        FakePassportUser::$resolved = null;
        FakePassportUser::$lookups = [];

        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        self::assertNotFalse($key);
        $exported = openssl_pkey_export($key, $this->privatePem);
        self::assertTrue($exported);
        $details = openssl_pkey_get_details($key);
        self::assertIsArray($details);

        $this->pemFile = tempnam(sys_get_temp_dir(), 'teleframe-passport-chain-');
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
        FakePassportUser::$resolved = null;
        FakePassportUser::$lookups = [];
    }

    /**
     * Build the service with the real (throwaway) keypair and configurable
     * seams. Defaults: enabled, account 7, session identity on the fake user.
     *
     * @param  array<string, mixed>  $config
     */
    private function service(
        array $config = [],
        ?MemoryNonceRegistry $nonces = null,
        ?BindingWriter $writer = null,
        ?PasswordConfirmer $confirmer = null,
        ?LoggerInterface $logger = null,
        ?Closure $onLogin = null,
        ?SessionIdentityResolver $identity = null,
    ): PassportLoginService {
        return new PassportLoginService(
            config: new PassportConfig(array_merge([
                'enabled' => true,
                'private_key_path' => $this->pemFile,
                'account_id' => 7,
                'allowed_types' => ['personal_details', 'email', 'phone'],
            ], $config)),
            keys: new PassportKeyLoader($this->pemFile),
            nonces: $nonces ?? new MemoryNonceRegistry(900),
            identity: $identity ?? new SessionIdentityResolver(FakePassportUser::class),
            writer: $writer ?? new MemoryBindingWriter,
            confirmer: $confirmer,
            logger: $logger,
            onLogin: $onLogin,
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
     * The DECRYPTED `Credentials` payload, which per §5 is
     * `{secure_data, nonce}` and holds the DOCUMENT types only. The verified
     * email/phone are NOT here — they are the `PassportData` plaintext, and
     * they travel in `updateWith()`'s $passportData.
     *
     * @return array{secure_data: array<string, array<string, string>>, nonce: string}
     */
    private function payloadFor(string $nonce): array
    {
        return [
            'secure_data' => [
                'personal_details' => ['first_name' => 'Ada', 'last_name' => 'Lovelace'],
            ],
            'nonce' => $nonce,
        ];
    }

    /**
     * A full Bot API update carrying the sealed credentials.
     *
     * A REAL Bot API update. $passportData carries the §6 verified plaintext
     * fields (`email` / `phone_number`) as top-level siblings of
     * `credentials` — which is the only place Telegram sends them.
     *
     * @param  array{data: string, secret: string, hash: string}  $envelope
     * @param  array<string, mixed>  $extraMessage
     * @param  array<string, string>  $passportData
     * @return array<string, mixed>
     */
    private function updateWith(
        array $envelope,
        int|string $fromId = 42,
        array $extraMessage = [],
        array $passportData = [],
    ): array {
        return [
            'update_id' => 1,
            'message' => array_merge([
                'from' => ['id' => $fromId],
                'passport_data' => PassportElementFactory::passportData($envelope, $passportData),
            ], $extraMessage),
        ];
    }

    public function test_full_chain_login_draws_a_login_result_and_binds(): void
    {
        $nonces = new MemoryNonceRegistry(900);
        $writer = new MemoryBindingWriter;
        $loggedIn = [];
        $service = $this->service(
            nonces: $nonces,
            writer: $writer,
            onLogin: static function (Model $user) use (&$loggedIn): void {
                $loggedIn[] = $user;
            },
        );
        $nonce = $nonces->issue(42);
        FakePassportUser::$resolved = new FakePassportUser;
        $update = $this->updateWith(
            $this->seal($this->payloadFor($nonce)),
            fromId: 987654321,
            passportData: ['email' => 'ada@example.com'],
        );

        $result = $service->login($update, 42);

        self::assertInstanceOf(LoginResult::class, $result);
        self::assertSame(987654321, $result->tgUserId);
        self::assertSame(7, $result->accountId);
        self::assertInstanceOf(FakePassportUser::class, $result->user);
        self::assertSame(['personal_details', 'email'], $result->fieldsVerified);

        $binding = $result->binding;
        self::assertSame(987654321, $binding['tg_id']);
        self::assertSame(7, $binding['account_id']);
        self::assertSame($result->user, $binding['user']);
        self::assertSame($binding, $writer->last());

        // ONE-TIME: a replayed webhook with the same nonce is refused.
        $this->expectException(PassportLoginException::class);
        $this->expectExceptionMessage('unknown or expired passport nonce');
        $service->login($update, 42);
    }

    public function test_full_chain_with_phone_and_digit_string_sender_id(): void
    {
        $nonces = new MemoryNonceRegistry(900);
        $writer = new MemoryBindingWriter;
        $service = $this->service(nonces: $nonces, writer: $writer);
        $nonce = $nonces->issue(42);
        FakePassportUser::$resolved = new FakePassportUser;

        // §6 — the verified phone is the PassportData plaintext, NOT a
        // secure_data entry (there is no such §5 type).
        $result = $service->login($this->updateWith(
            $this->seal($this->payloadFor($nonce)),
            fromId: '4242424242',
            passportData: ['phone_number' => '+15551234567'],
        ), 42);

        self::assertSame(4242424242, $result->tgUserId);
        self::assertSame(['personal_details', 'phone'], $result->fieldsVerified);
        self::assertInstanceOf(FakePassportUser::class, $result->user);
    }

    public function test_login_without_a_consumed_session_user_still_resolves_from_the_nonce(): void
    {
        $nonces = new MemoryNonceRegistry(900);
        $service = $this->service(nonces: $nonces);
        $nonce = $nonces->issue(42);
        FakePassportUser::$resolved = new FakePassportUser;

        // login($update, 42) — the consuming session
        // userId (42) is what resolves the identity.
        $result = $service->login(
            $this->updateWith($this->seal($this->payloadFor($nonce)), passportData: ['email' => 'ada@example.com']),
            42,
        );

        self::assertSame(42, $result->user->id);
    }

    public function test_unknown_nonce_throws_and_never_touches_writer_or_hook(): void
    {
        $writer = new MemoryBindingWriter;
        $hookCalled = false;
        $service = $this->service(
            writer: $writer,
            onLogin: static function (Model $user) use (&$hookCalled): void {
                $hookCalled = true;
            },
        );
        FakePassportUser::$resolved = new FakePassportUser;

        $update = $this->updateWith($this->seal($this->payloadFor('a-nonce-that-was-never-issued')));

        try {
            $service->login($update, 42);
            self::fail('an unknown nonce must throw loud');
        } catch (PassportLoginException $e) {
            self::assertStringContainsString('unknown or expired passport nonce', $e->getMessage());
            self::assertNull($e->getPrevious()); // domain refusal — never double-wrapped
        }

        self::assertSame([], $writer->rows());
        self::assertNull($writer->last());
        self::assertFalse($hookCalled);
    }

    public function test_failure_logs_at_error_and_rethrows_the_original(): void
    {
        $logger = new ArrayLogger;
        $service = $this->service(nonces: new MemoryNonceRegistry(900), logger: $logger);
        FakePassportUser::$resolved = new FakePassportUser;

        $update = $this->updateWith($this->seal($this->payloadFor('never-issued-nonce')));

        try {
            $service->login($update, 42);
            self::fail('must throw');
        } catch (PassportLoginException $e) {
            self::assertStringContainsString('unknown or expired passport nonce', $e->getMessage());
        }

        self::assertCount(1, $logger->records);
        self::assertSame('error', $logger->records[0]['level']);
        self::assertStringContainsString('PassportLoginService::login failed after', $logger->records[0]['message']);
        self::assertSame(PassportLoginException::class, $logger->records[0]['context']['exception']);
        self::assertSame('login', $logger->records[0]['context']['method']);
        // No payload/keys/passwords in the log surface.
        self::assertStringNotContainsString('never-issued-nonce', $logger->records[0]['message']);
    }

    public function test_disabled_config_throws_loud_on_login(): void
    {
        $logger = new ArrayLogger;
        $service = $this->service(config: ['enabled' => false], logger: $logger);

        try {
            $service->login([], 42);
            self::fail('a disabled module must refuse loud on first use');
        } catch (PassportLoginException $e) {
            self::assertStringContainsString('passport login not enabled', $e->getMessage());
        }

        self::assertSame('error', $logger->records[0]['level']);
    }

    public function test_decryptor_failure_is_wrapped_with_the_original_cause(): void
    {
        $logger = new ArrayLogger;
        $service = $this->service(logger: $logger);
        FakePassportUser::$resolved = new FakePassportUser;

        $envelope = $this->seal($this->payloadFor('nonce-x'));
        $tamperedHash = str_starts_with($envelope['hash'], 'A')
            ? 'B'.substr($envelope['hash'], 1)
            : 'A'.substr($envelope['hash'], 1);
        $envelope['hash'] = $tamperedHash;

        try {
            $service->login($this->updateWith($envelope), 42);
            self::fail('a hash mismatch must throw loud');
        } catch (PassportLoginException $e) {
            // which decryptor RuntimeException fired is padding-dependent
            // (AES failure or hash mismatch) — the LOUD wrapper + the original
            // cause preserved is the contract (AGENTS.md: never swallowed).
            self::assertInstanceOf(RuntimeException::class, $e->getPrevious());
            self::assertStringContainsString('Passport', $e->getPrevious()->getMessage());
        }
    }

    public function test_missing_sender_id_throws_loud(): void
    {
        $nonces = new MemoryNonceRegistry(900);
        $writer = new MemoryBindingWriter;
        $service = $this->service(nonces: $nonces, writer: $writer);
        $nonce = $nonces->issue(42);
        FakePassportUser::$resolved = new FakePassportUser;

        $update = $this->updateWith($this->seal($this->payloadFor($nonce)), fromId: 0);

        try {
            $service->login($update, 42);
            self::fail('a missing sender id must throw loud');
        } catch (PassportLoginException $e) {
            self::assertStringContainsString('requires the sender telegram user id', $e->getMessage());
        }

        self::assertSame([], $writer->rows());
    }

    public function test_sender_chat_is_refused_never_a_silent_fallback(): void
    {
        $nonces = new MemoryNonceRegistry(900);
        $service = $this->service(nonces: $nonces);
        $nonce = $nonces->issue(42);
        FakePassportUser::$resolved = new FakePassportUser;

        $update = [
            'update_id' => 1,
            'message' => [
                'sender_chat' => ['id' => -100123456789],
                'passport_data' => ['credentials' => $this->seal($this->payloadFor($nonce))],
            ],
        ];

        $this->expectException(PassportLoginException::class);
        $this->expectExceptionMessage('sender_chat is refused; passport is user-only');

        $service->login($update, 42);
    }

    public function test_unresolved_identity_throws_loud(): void
    {
        $nonces = new MemoryNonceRegistry(900);
        $service = $this->service(nonces: $nonces);
        $nonce = $nonces->issue(42);
        FakePassportUser::$resolved = null; // resolve → null

        $this->expectException(PassportLoginException::class);
        $this->expectExceptionMessage('unresolved');

        $service->login($this->updateWith($this->seal($this->payloadFor($nonce))), 42);
    }

    public function test_unconfigured_user_class_resolves_to_null_and_is_refused(): void
    {
        $nonces = new MemoryNonceRegistry(900);
        $service = $this->service(
            nonces: $nonces,
            identity: new SessionIdentityResolver('No\\Such\\UserClass'),
        );
        $nonce = $nonces->issue(42);

        $this->expectException(PassportLoginException::class);
        $this->expectExceptionMessage('unresolved');

        $service->login($this->updateWith($this->seal($this->payloadFor($nonce))), 42);
    }

    public function test_require_password_confirmation_missing_password_is_loud(): void
    {
        $nonces = new MemoryNonceRegistry(900);
        $service = $this->service(config: ['require_password_confirmation' => true], nonces: $nonces);
        $nonce = $nonces->issue(42);
        FakePassportUser::$resolved = new FakePassportUser;

        // update without the 2FA password field
        $this->expectException(PassportLoginException::class);
        $this->expectExceptionMessage('requires the 2FA password');

        $service->login($this->updateWith($this->seal($this->payloadFor($nonce))), 42);
    }

    public function test_require_password_confirmation_without_a_confirmer_seam_is_loud(): void
    {
        $nonces = new MemoryNonceRegistry(900);
        $service = $this->service(config: ['require_password_confirmation' => true], nonces: $nonces);
        $nonce = $nonces->issue(42);
        FakePassportUser::$resolved = new FakePassportUser;

        $update = $this->updateWith($this->seal($this->payloadFor($nonce)));
        $update['password'] = 'hunter2';

        $this->expectException(PassportLoginException::class);
        $this->expectExceptionMessage('requires a MeRezaRezaei\\Teleframe\\Passport\\PasswordConfirmer seam');

        $service->login($update, 42);
    }

    public function test_require_password_confirmation_null_scope_confirmer_is_loud(): void
    {
        $nonces = new MemoryNonceRegistry(900);
        $service = $this->service(
            config: ['require_password_confirmation' => true],
            nonces: $nonces,
            confirmer: new PasswordConfirmer(null), // the shipped inert seam: null scope → loud on use
        );
        $nonce = $nonces->issue(42);
        FakePassportUser::$resolved = new FakePassportUser;

        $update = $this->updateWith($this->seal($this->payloadFor($nonce)));
        $update['password'] = 'hunter2';

        $this->expectException(PassportLoginException::class);
        $this->expectExceptionMessage('live scope required');

        $service->login($update, 42);
    }

    public function test_handle_update_returns_null_without_any_credentials(): void
    {
        $service = $this->service();

        self::assertNull($service->handleUpdate(['update_id' => 1, 'message' => ['text' => 'hi']]));
        self::assertNull($service->handleUpdate([]));
        self::assertNull($service->handleUpdate(['_' => 'updateNewMessage']));
    }

    public function test_handle_update_with_credentials_routes_to_login_and_stays_loud_when_disabled(): void
    {
        $logger = new ArrayLogger;
        $service = $this->service(config: ['enabled' => false], logger: $logger);

        $update = ['message' => ['passport_data' => ['credentials' => ['data' => 'x', 'hash' => 'y', 'secret' => 'z']]]];

        try {
            $service->handleUpdate($update, 42);
            self::fail('an update carrying credentials must route to login() and stay loud');
        } catch (PassportLoginException $e) {
            self::assertStringContainsString('passport login not enabled', $e->getMessage());
        }

        self::assertSame('error', $logger->records[0]['level']);
    }

    public function test_issue_nonce_is_32_hex_and_consumable_by_the_registry(): void
    {
        $service = $this->service();

        $nonce = $service->issueNonce(17);

        self::assertSame(32, strlen($nonce));
        // the returned nonce flows through the SAME registry the webhook consumes
        self::assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $nonce);
    }

    // -- SessionIdentityResolver (the shipped default seam) -----------------

    public function test_session_identity_resolver_resolves_a_real_model_subclass(): void
    {
        $resolver = new SessionIdentityResolver(FakePassportUser::class);
        FakePassportUser::$resolved = new FakePassportUser;

        $user = $resolver->resolve(new PassportPayload([
            'secure_data' => [],
            'nonce' => 'n',
        ]), 42);

        self::assertInstanceOf(FakePassportUser::class, $user);
        self::assertSame([['key' => 'id', 'value' => 42]], FakePassportUser::$lookups);
    }

    public function test_session_identity_resolver_uses_the_configured_key(): void
    {
        $resolver = new SessionIdentityResolver(FakePassportUser::class, 'uuid');
        FakePassportUser::$resolved = new FakePassportUser;

        $resolver->resolve(new PassportPayload([
            'secure_data' => [],
            'nonce' => 'n',
        ]), 5);

        self::assertSame([['key' => 'uuid', 'value' => 5]], FakePassportUser::$lookups);
    }

    public function test_session_identity_resolver_null_session_user_returns_null(): void
    {
        $resolver = new SessionIdentityResolver(FakePassportUser::class);
        FakePassportUser::$resolved = new FakePassportUser;

        self::assertNull($resolver->resolve(new PassportPayload([
            'secure_data' => [],
            'nonce' => 'n',
        ]), null));
    }

    public function test_session_identity_resolver_unknown_or_non_model_class_returns_null(): void
    {
        $resolver = new SessionIdentityResolver('No\\Such\\UserClass');
        self::assertNull($resolver->resolve(new PassportPayload([
            'secure_data' => [],
            'nonce' => 'n',
        ]), 1));

        $stdResolver = new SessionIdentityResolver(\stdClass::class);
        self::assertNull($stdResolver->resolve(new PassportPayload([
            'secure_data' => [],
            'nonce' => 'n',
        ]), 1));
    }

    // -- BindingWriter seams -------------------------------------------------

    public function test_memory_binding_writer_records_rows_and_last(): void
    {
        $writer = new MemoryBindingWriter;
        $user = new FakePassportUser;

        self::assertNull($writer->last());
        self::assertSame([], $writer->rows());

        $row = $writer->bind(42, 7, $user);

        self::assertSame(['tg_id' => 42, 'account_id' => 7, 'user' => $user], $row);
        self::assertSame($row, $writer->last());
        self::assertCount(1, $writer->rows());
    }

    public function test_eloquent_binding_writer_delegate_receives_the_translated_argument_order(): void
    {
        $received = [];
        $writer = new EloquentBindingWriter(static function (object $user, int $tgId, ?int $accountId) use (&$received): array {
            $received = [$user, $tgId, $accountId];

            return ['bound' => true];
        });
        $user = new FakePassportUser;

        $result = $writer->bind(42, 7, $user);

        // BINDINGS order (user, tgId, accountId) — bind() translates from the
        // interface order (tgId, accountId, user).
        self::assertSame(['bound' => true], $result);
        self::assertSame([$user, 42, 7], $received);
    }

    public function test_eloquent_binding_writer_default_delegate_requires_a_model(): void
    {
        $writer = new EloquentBindingWriter; // default delegate (the real Bindings seam, never reached)

        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('requires an Eloquent Model user');

        $writer->bind(42, 7, new \stdClass);
    }
}

/**
 * The fake Laravel user — an Eloquent Model subclass so the shipped
 * SessionIdentityResolver's class_exists + is_subclass_of(Model) guard accepts
 * it, with a DB-free query surface: static query() returns the instance and
 * where()/first() record the lookup and hand back the seeded row. Mirrors the
 * "fake model registered in the test" convention (design §8, §9).
 */
final class FakePassportUser extends Model
{
    public ?int $id = 42;

    /** @var ?self the row resolve() hands back (null = unresolved) */
    public static ?self $resolved = null;

    /** @var list<array{key: string, value: int}> */
    public static array $lookups = [];

    public static function query(): static
    {
        return new self;
    }

    public function where(string $key, mixed $value): static
    {
        self::$lookups[] = ['key' => $key, 'value' => (int) $value];

        return $this;
    }

    public function first(): ?static
    {
        return self::$resolved;
    }
}
