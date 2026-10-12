<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Passport;

use MeRezaRezaei\Teleframe\Passport\MemoryBindingWriter;
use MeRezaRezaei\Teleframe\Passport\MemoryPendingLoginRegistry;
use MeRezaRezaei\Teleframe\Passport\PassportAnonymousLoginService;
use MeRezaRezaei\Teleframe\Passport\PassportConfig;
use MeRezaRezaei\Teleframe\Passport\PassportKeyLoader;
use MeRezaRezaei\Teleframe\Passport\PassportLoginException;
use MeRezaRezaei\Teleframe\Passport\PendingLoginRegistry;
use MeRezaRezaei\Teleframe\Passport\SessionFingerprint;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * PassportAnonymousLoginService — the orchestrator, tested PLAIN-PHP.
 *
 * The anonymous journey is proved end to end over HTTP in
 * `tests/Laravel/Http/Dashboard/PassportAnonymousLoginFlowTest.php`; this file
 * covers what an HTTP test structurally cannot see:
 *
 *  - the chain is INERT by default, before any request is even imagined;
 *  - `start()` refuses unless the capability is on, so an unconfigured host
 *    cannot mint a marker or a nonce on this path;
 *  - the fingerprint is a REQUIRED argument, asserted at the signature rather
 *    than at a call site, which is where both earlier takeovers lived;
 *  - the service NEVER calls Auth itself — it invokes an injected ride, so the
 *    host keeps ownership of what a login means.
 *
 * Plain-PHP throughout: no container, no database, no config repository. That
 * is also the standalone-smoke posture, and this suite is its PHPUnit mirror.
 */
final class PassportAnonymousLoginServiceTest extends TestCase
{
    // ── inert by default ──────────────────────────────────────────────

    public function test_a_fully_default_config_refuses_to_start(): void
    {
        $service = $this->service(new PassportConfig([]));

        $refused = false;
        try {
            $service->start(SessionFingerprint::of('a-session'));
        } catch (PassportLoginException $e) {
            $refused = true;
            self::assertStringContainsString('enabled', $e->getMessage());
        }

        self::assertTrue($refused, 'a default config must never mint a marker on this path');
    }

    public function test_enabling_the_module_alone_does_not_enable_the_login(): void
    {
        // `enabled` is the ATTACH flow. The login is a separate switch, and a
        // host that has only ever heard of `enabled` must not get a login.
        $service = $this->service(new PassportConfig(['enabled' => true]));

        $this->assertRefused(
            static fn () => $service->start(SessionFingerprint::of('a-session')),
            'anonymous_login',
        );
    }

    public function test_login_itself_refuses_when_the_capability_is_off(): void
    {
        $service = $this->service(new PassportConfig([]));

        $this->assertRefused(
            static fn () => $service->login(
                ['message' => ['from' => ['id' => 1]]],
                'a-token',
                SessionFingerprint::of('a-session'),
            ),
            'disabled',
        );
    }

    // ── start() mints through the registry, bound to the fingerprint ──

    public function test_start_binds_the_marker_to_the_fingerprint_it_was_given(): void
    {
        $pending = new MemoryPendingLoginRegistry(60);
        $service = $this->service($this->onConfig(), $pending);

        $fingerprint = SessionFingerprint::of('session-a');
        $ticket = $service->start($fingerprint);

        $binding = $pending->consume($ticket->token());

        self::assertNotNull($binding);
        self::assertSame($fingerprint, $binding->sessionFingerprint());
        self::assertSame(hash('sha256', $ticket->nonce()), $binding->nonceDigest());
    }

    public function test_start_refuses_a_blank_fingerprint_rather_than_binding_to_nothing(): void
    {
        $service = $this->service($this->onConfig());

        // The formula refuses a blank id, so a caller that has no session cannot
        // even mint — let alone bind to a constant every other sessionless
        // caller shares.
        $this->assertRefused(static fn () => $service->start(''), 'blank');
    }

    // ── the binding is a type-level property ──────────────────────────

    /**
     * Both earlier takeovers in this module were "the check could be skipped by
     * omitting an argument": first a `?int $sessionUserId = null`, then a
     * check the HTTP layer could route around. So the property is asserted
     * where it is now enforced — the signature — and a regression to a nullable
     * or defaulted fingerprint fails here even if every behavioural test still
     * passed.
     */
    public function test_the_fingerprint_is_a_required_argument_of_login(): void
    {
        $params = (new ReflectionMethod(PassportAnonymousLoginService::class, 'login'))->getParameters();

        $fingerprint = $params[2];
        self::assertSame('sessionFingerprint', $fingerprint->getName());
        self::assertFalse($fingerprint->allowsNull(), 'a nullable fingerprint is the first takeover, verbatim');
        self::assertFalse($fingerprint->isOptional(), 'an omittable fingerprint skips the comparison');
        self::assertFalse($fingerprint->isDefaultValueAvailable());
        self::assertSame('string', (string) $fingerprint->getType());

        // The handle cannot be omitted either — a callback must present one.
        $flow = $params[1];
        self::assertFalse($flow->allowsNull());
        self::assertFalse($flow->isDefaultValueAvailable());
    }

    public function test_start_takes_the_fingerprint_and_the_service_exposes_itself_for_the_facade_probe(): void
    {
        $service = $this->service($this->onConfig());

        $params = (new ReflectionMethod($service, 'start'))->getParameters();
        self::assertCount(1, $params);
        self::assertSame('sessionFingerprint', $params[0]->getName());
        self::assertFalse($params[0]->allowsNull());
        self::assertFalse($params[0]->isOptional());

        self::assertSame($service, $service->service());
    }

    // ── the package never authenticates by itself ─────────────────────

    /**
     * The ride is INJECTED. The service holds no Auth factory, no guard and no
     * session: it resolves a user and hands it to whatever the host installed.
     * That is why the anonymous path can create a session without the package
     * owning what a session means.
     */
    public function test_the_auth_ride_is_an_injected_hook_not_an_auth_call(): void
    {
        $seen = [];
        $service = new PassportAnonymousLoginService(
            config: $this->onConfig(),
            keys: new PassportKeyLoader('/nonexistent.pem'),
            pending: new MemoryPendingLoginRegistry(60),
            writer: new MemoryBindingWriter,
            onLogin: static function (object $user) use (&$seen): void {
                $seen[] = $user;
            },
        );

        self::assertNotNull($service, 'the ride is a constructor argument, so it is a plain closure here');

        // Nothing else to assert without running the chain: the point is that
        // the class has no Auth dependency at all, which the constructor
        // signature itself states.
        $ctor = (new ReflectionMethod(PassportAnonymousLoginService::class, '__construct'))->getParameters();
        $names = array_map(static fn ($p): string => $p->getName(), $ctor);

        self::assertContains('onLogin', $names);
        foreach ($names as $name) {
            self::assertStringNotContainsStringIgnoringCase('guard', $name);
            self::assertStringNotContainsStringIgnoringCase('session', $name === 'onLogin' ? 'x' : $name);
        }
    }

    // ── the chain's own refusals, driven with REAL credentials ───────

    /**
     * The service refuses to REPLACE a live session, and that refusal must not
     * live only in the controller: a non-HTTP caller (a queue listener, a host's
     * own endpoint, a future surface) reaches this class directly, and the
     * takeover this prevents — swapping an authenticated browser onto whoever
     * the claim names — does not care where the call came from.
     *
     * Driven with a REAL marker and REAL crypto rather than a stub, because a
     * test that cannot reach the check is not a test of the check: the marker
     * is issued through the registry so the fingerprint and nonce digest are
     * genuine, and the payload is sealed against a throwaway keypair so every
     * step before the refusal really passes.
     */
    public function test_the_chain_refuses_to_replace_a_live_session(): void
    {
        [$service, $pending, $pem] = $this->chainService();

        $fingerprint = SessionFingerprint::of('session-a');
        $ticket = $pending->issue($fingerprint);
        // §5: the blob holds the document types. §6: the verified email is the
        // `PassportData` plaintext, beside the credentials.
        $credentials = $this->seal([
            'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
            'nonce' => $ticket->nonce(),
        ], $pem['private'], $pem['public']);

        $update = [
            'message' => [
                'from' => ['id' => 4242],
                'passport_data' => PassportElementFactory::passportData(
                    $credentials,
                    ['email' => 'x@y.test'],
                ),
            ],
        ];

        // Without the live-session guard this would log the caller in as
        // whoever the claim names.
        $refused = false;
        try {
            $service->login($update, $ticket->token(), $fingerprint, 7);
        } catch (PassportLoginException $e) {
            $refused = true;
            self::assertStringContainsString('already authenticated', $e->getMessage());
        }

        self::assertTrue($refused, 'a claim must never replace a live session');
    }

    /**
     * The same, in the other direction: with NO live session the identical
     * credentials get past the guard and on to identity resolution, so the
     * refusal above is about the LIVE SESSION and not about the credentials
     * being unusable. Without this, the test above could pass for the wrong
     * reason.
     */
    public function test_the_same_credentials_are_not_refused_for_any_other_reason(): void
    {
        [$service, $pending, $pem] = $this->chainService();

        $fingerprint = SessionFingerprint::of('session-a');
        $ticket = $pending->issue($fingerprint);
        // §5: the blob holds the document types. §6: the verified email is the
        // `PassportData` plaintext, beside the credentials.
        $credentials = $this->seal([
            'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
            'nonce' => $ticket->nonce(),
        ], $pem['private'], $pem['public']);

        $update = [
            'message' => [
                'from' => ['id' => 4242],
                'passport_data' => PassportElementFactory::passportData(
                    $credentials,
                    ['email' => 'x@y.test'],
                ),
            ],
        ];

        // The claim names no user (the lookup is disabled here), so the chain
        // gets all the way to the lookup and refuses THERE — proving the
        // credentials, the marker, the fingerprint and the nonce all passed.
        $message = '';
        try {
            $service->login($update, $ticket->token(), $fingerprint, 0);
        } catch (PassportLoginException $e) {
            $message = $e->getMessage();
        }

        self::assertStringContainsString('no Laravel user matches', $message);
        self::assertStringNotContainsString('already authenticated', $message);
        self::assertStringNotContainsString('different browser', $message);
        self::assertStringNotContainsString('this login flow', $message);
    }

    // ── helpers ───────────────────────────────────────────────────────

    /**
     * A service wired for the CHAIN: a real key, a real marker store, and no
     * user lookup (so the chain always ends at a refusal we can name).
     *
     * @return array{0: PassportAnonymousLoginService, 1: PendingLoginRegistry, 2: array{public: string, private: string, path: string}}
     */
    private function chainService(): array
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        self::assertNotFalse($key);
        self::assertTrue(openssl_pkey_export($key, $private));
        $details = openssl_pkey_get_details($key);
        self::assertIsArray($details);

        $path = (string) tempnam(sys_get_temp_dir(), 'teleframe-anon-chain-');
        file_put_contents($path, $private);
        chmod($path, 0o600);

        $pending = new MemoryPendingLoginRegistry(60);
        $service = new PassportAnonymousLoginService(
            config: $this->onConfig(),
            keys: new PassportKeyLoader($path),
            pending: $pending,
            writer: new MemoryBindingWriter,
            userClass: '', // no lookup: the chain refuses at identity resolution
        );

        return [$service, $pending, ['public' => (string) $details['key'], 'private' => $private, 'path' => $path]];
    }

    /**
     * Seal a `Credentials` payload with the SPEC-FAITHFUL builder
     * (`SpecCredentialFactory`), against the keypair `chainService()` made.
     *
     * The previous body built the fixture to match the implementation — hash
     * over the unpadded plaintext, no Telegram padding, OpenSSL PKCS#7 on the
     * ciphertext — so it agreed with the code by construction and could not
     * catch a spec violation. See `SpecCredentialFactory` for the upstream
     * build order it now transcribes.
     *
     * @param  array<string, mixed>  $payload
     * @return array{data: string, secret: string, hash: string}
     */
    private function seal(array $payload, string $privatePem, string $publicPem): array
    {
        return SpecCredentialFactory::withKeys($privatePem, $publicPem)->seal($payload);
    }

    private function onConfig(): PassportConfig
    {
        return new PassportConfig(['enabled' => true, 'anonymous_login' => true]);
    }

    private function service(PassportConfig $config, ?PendingLoginRegistry $pending = null): PassportAnonymousLoginService
    {
        return new PassportAnonymousLoginService(
            config: $config,
            keys: new PassportKeyLoader('/nonexistent.pem'), // never read — nothing decrypts here
            pending: $pending ?? new MemoryPendingLoginRegistry(60),
            writer: new MemoryBindingWriter,
            userClass: '', // no lookup: these tests never resolve a user
        );
    }

    private function assertRefused(callable $call, string $needle): void
    {
        $refused = false;
        try {
            $call();
        } catch (PassportLoginException $e) {
            $refused = true;
            self::assertStringContainsString($needle, $e->getMessage());
        }

        self::assertTrue($refused, 'expected a loud refusal mentioning "'.$needle.'"');
    }
}
