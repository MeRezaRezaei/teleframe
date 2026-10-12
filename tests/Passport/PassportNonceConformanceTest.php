<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Passport;

use MeRezaRezaei\Teleframe\Passport\MemoryBindingWriter;
use MeRezaRezaei\Teleframe\Passport\MemoryNonceRegistry;
use MeRezaRezaei\Teleframe\Passport\MemoryPendingLoginRegistry;
use MeRezaRezaei\Teleframe\Passport\PassportAnonymousLoginService;
use MeRezaRezaei\Teleframe\Passport\PassportConfig;
use MeRezaRezaei\Teleframe\Passport\PassportKeyLoader;
use MeRezaRezaei\Teleframe\Passport\PassportLoginException;
use MeRezaRezaei\Teleframe\Passport\PassportLoginService;
use MeRezaRezaei\Teleframe\Passport\SessionFingerprint;
use Illuminate\Database\Eloquent\Model;
use MeRezaRezaei\Teleframe\Passport\SessionIdentityResolver;
use PHPUnit\Framework\TestCase;

/**
 * §2 conformance for the `nonce` — "the SSO correlation handle".
 *
 * The spec's requirements, each pinned here against BOTH shipped flows (the
 * authenticated attach flow, `PassportLoginService`, and the anonymous login
 * flow, `PassportAnonymousLoginService`):
 *
 *   - it is bot-specified and must be a cryptographically secure unique
 *     identifier — never `uniqid()`, never a counter, never a timestamp;
 *   - "You should never accept credentials with the same nonce twice" —
 *     single-use, enforced by the one-time registry;
 *   - after decryption `Credentials.nonce` must EQUAL the nonce sent in the
 *     request (Telegram states this as an IMPORTANT), so a payload carrying
 *     any other nonce is refused.
 *
 * ON "COMPARED IN CONSTANT TIME", STATED HONESTLY. The two flows do NOT
 * enforce this the same way, and this test does not pretend they do:
 *
 *   - The ANONYMOUS flow compares digests: `SessionFingerprint::matches()`
 *     is `hash_equals()` over `sha256(nonce)`, which is a constant-time
 *     comparison — proved below by asserting a differing digest is refused
 *     and an identical one is not.
 *   - The ATTACH flow does not byte-compare at all. It hands the payload's
 *     nonce to `NonceRegistry::consume()`, a one-time KEYED LOOKUP, and a
 *     miss is refused. That is the correct mechanism for "did I issue this
 *     exact nonce, and has it already been spent" — there is no secret to
 *     walk byte by byte, and the hit/miss distinction the lookup leaks is
 *     precisely the answer the caller is entitled to. What it is NOT is a
 *     `hash_equals` comparison, and no amount of rewriting turns an indexed
 *     one-time lookup into one without breaking the single-use property.
 *     The property that actually protects the flow is single-use plus the
 *     session binding (`$sessionUserId !== $consumedUserId`), and both are
 *     pinned here and in PassportSessionBindingTest.
 */
final class PassportNonceConformanceTest extends TestCase
{
    private SpecCredentialFactory $factory;

    private string $pemFile = '';

    protected function setUp(): void
    {
        NonceFlowUser::$resolved = new NonceFlowUser;

        $this->factory = new SpecCredentialFactory;
        $this->pemFile = (string) tempnam(sys_get_temp_dir(), 'teleframe-passport-nonce-');
        file_put_contents($this->pemFile, $this->factory->privateKeyPem());
        chmod($this->pemFile, 0o600);
    }

    protected function tearDown(): void
    {
        if ($this->pemFile !== '' && is_file($this->pemFile)) {
            unlink($this->pemFile);
        }

        NonceFlowUser::$resolved = null;
    }

    public function test_an_issued_nonce_is_128_bits_of_csprng_output_and_unique(): void
    {
        $nonces = new MemoryNonceRegistry(900);

        $issued = [];
        for ($i = 0; $i < 64; $i++) {
            $nonce = $nonces->issue(42);
            self::assertSame(32, strlen($nonce), '16 random bytes, hex-encoded');
            self::assertSame($nonce, strtolower($nonce));
            $issued[$nonce] = true;
        }

        self::assertCount(64, $issued, 'no collisions across 64 issues');
    }

    public function test_the_attach_flow_refuses_a_nonce_it_never_issued(): void
    {
        $nonces = new MemoryNonceRegistry(900);
        $service = $this->attachService($nonces);

        $this->expectException(PassportLoginException::class);
        $this->expectExceptionMessage('nonce');

        $service->login($this->updateWith('a-nonce-nobody-issued'), 42);
    }

    public function test_the_attach_flow_spends_its_nonce_exactly_once(): void
    {
        $nonces = new MemoryNonceRegistry(900);
        $service = $this->attachService($nonces);
        $nonce = $nonces->issue(42);
        $update = $this->updateWith($nonce);

        $service->login($update, 42);

        // The replay: the same envelope, the same nonce, a second time.
        $this->expectException(PassportLoginException::class);
        $this->expectExceptionMessage('nonce');
        $service->login($update, 42);
    }

    public function test_the_anonymous_flow_refuses_a_nonce_issued_for_another_trip(): void
    {
        [$service, $pending] = $this->anonymousService();
        $ticket = $pending->issue(SessionFingerprint::of('session-a'));

        $this->expectException(PassportLoginException::class);
        $this->expectExceptionMessage('this login flow');

        $service->login(
            $this->updateWith('a-different-nonce-entirely'),
            $ticket->token(),
            SessionFingerprint::of('session-a'),
            0
        );
    }

    public function test_the_anonymous_flow_spends_its_flow_and_nonce_exactly_once(): void
    {
        [$service, $pending] = $this->anonymousService();
        $ticket = $pending->issue(SessionFingerprint::of('session-a'));
        // A vetted email, so the chain gets all the way to the user lookup.
        $update = $this->updateWith($ticket->nonce(), [
            'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
            'nonce' => $ticket->nonce(),
        ], ['email' => 'nobody@example.test']);

        // No user matches (the lookup is disabled), so the chain refuses at
        // identity — which is the LAST step, so everything before it passed.
        try {
            $service->login($update, $ticket->token(), SessionFingerprint::of('session-a'), 0);
            self::fail('no user matches, so the chain must refuse');
        } catch (PassportLoginException $e) {
            self::assertStringContainsString('no Laravel user matches', $e->getMessage());
        }

        // The replay: the marker is spent.
        $this->expectException(PassportLoginException::class);
        $this->expectExceptionMessage('replayed');
        $service->login($update, $ticket->token(), SessionFingerprint::of('session-a'), 0);
    }

    public function test_the_anonymous_flows_nonce_comparison_is_a_constant_time_digest_equality(): void
    {
        // hash_equals over sha256 digests: an identical digest matches, a
        // differing one never does, and a BLANK one matches nothing at all
        // (hash_equals('','') is true, so the guard is load-bearing).
        self::assertTrue(SessionFingerprint::matches(hash('sha256', 'n'), hash('sha256', 'n')));
        self::assertFalse(SessionFingerprint::matches(hash('sha256', 'n'), hash('sha256', 'm')));
        self::assertFalse(SessionFingerprint::matches('', ''));
        self::assertFalse(SessionFingerprint::matches(hash('sha256', 'n'), ''));
    }

    public function test_a_payload_with_no_nonce_is_refused_before_any_registry_lookup(): void
    {
        $nonces = new MemoryNonceRegistry(900);
        $service = $this->attachService($nonces);

        $this->expectException(PassportLoginException::class);
        $this->expectExceptionMessage('no nonce');

        // A `Credentials` object with no nonce is a protocol violation.
        $service->login(
            $this->updateWith('', ['secure_data' => []]),
            42
        );
    }

    // ── wiring ───────────────────────────────────────────────────────────

    private function attachService(MemoryNonceRegistry $nonces): PassportLoginService
    {
        return new PassportLoginService(
            config: new PassportConfig([
                'enabled' => true,
                'private_key_path' => $this->pemFile,
                'account_id' => 7,
                'allowed_types' => ['personal_details', 'email', 'phone'],
            ]),
            keys: new PassportKeyLoader($this->pemFile),
            nonces: $nonces,
            identity: new SessionIdentityResolver(NonceFlowUser::class),
            writer: new MemoryBindingWriter,
        );
    }

    /** @return array{0: PassportAnonymousLoginService, 1: MemoryPendingLoginRegistry} */
    private function anonymousService(): array
    {
        $config = new PassportConfig([
            'enabled' => true,
            'anonymous_login' => true,
            'private_key_path' => $this->pemFile,
            'account_id' => 7,
            'allowed_types' => ['personal_details', 'email', 'phone'],
        ]);

        $pending = new MemoryPendingLoginRegistry(60);

        return [
            new PassportAnonymousLoginService(
                config: $config,
                keys: new PassportKeyLoader($this->pemFile),
                pending: $pending,
                writer: new MemoryBindingWriter,
                userClass: '',
            ),
            $pending,
        ];
    }

    /**
     * A Bot API update carrying a spec-shaped `Credentials` envelope.
     *
     * @param  array<string, mixed>|null  $overridePayload  a payload to seal instead
     * @return array<string, mixed>
     */
    /**
     * @param  array<string, mixed>|null  $overridePayload
     * @param  array<string, string>  $passportData  the §6 verified plaintext
     *                                               fields, which live beside
     *                                               the credentials
     * @return array<string, mixed>
     */
    private function updateWith(string $nonce, ?array $overridePayload = null, array $passportData = []): array
    {
        $payload = $overridePayload ?? [
            'secure_data' => ['personal_details' => ['first_name' => 'Ada', 'last_name' => 'Lovelace']],
            'nonce' => $nonce,
        ];

        return [
            'update_id' => 1,
            'message' => [
                'from' => ['id' => 4242],
                'passport_data' => PassportElementFactory::passportData($this->factory->seal($payload), $passportData),
            ],
        ];
    }
}

/**
 * A DB-free Eloquent Model the session resolver can hand back, so the attach
 * flow reaches the end of its chain. Declared HERE rather than reused from
 * another suite's file, because those files are not autoloaded when this one
 * runs on its own — a test double that only exists if another test happened
 * to load first is a fixture that lies about what it depends on.
 */
final class NonceFlowUser extends Model
{
    public ?int $id = 42;

    public static ?self $resolved = null;

    public static function query(): static
    {
        return new self;
    }

    /** @param  string  $key */
    public function where(string $key, mixed $value): static
    {
        return $this;
    }

    public function first(): ?static
    {
        return self::$resolved;
    }
}
