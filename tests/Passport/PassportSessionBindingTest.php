<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Passport;

use Closure;
use Illuminate\Database\Eloquent\Model;
use MeRezaRezaei\Teleframe\Laravel\Facades\Passport;
use MeRezaRezaei\Teleframe\Laravel\PassportServiceProvider;
use MeRezaRezaei\Teleframe\Passport\BindingWriter;
use MeRezaRezaei\Teleframe\Passport\FieldIdentityResolver;
use MeRezaRezaei\Teleframe\Passport\IdentityResolver;
use MeRezaRezaei\Teleframe\Passport\LoginResult;
use MeRezaRezaei\Teleframe\Passport\MemoryBindingWriter;
use MeRezaRezaei\Teleframe\Passport\MemoryNonceRegistry;
use MeRezaRezaei\Teleframe\Passport\PassportConfig;
use MeRezaRezaei\Teleframe\Passport\PassportKeyLoader;
use MeRezaRezaei\Teleframe\Passport\PassportLoginException;
use MeRezaRezaei\Teleframe\Passport\PassportLoginService;
use MeRezaRezaei\Teleframe\Passport\PassportPayload;
use MeRezaRezaei\Teleframe\Passport\PassportVerifiedClaims;
use MeRezaRezaei\Teleframe\Passport\SessionIdentityResolver;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * PassportSessionBindingTest — the SECOND account-takeover path (2026-09-26).
 *
 * Commit 5cbbde5b added a binding check and shipped green, because the check
 * was `if ($sessionUserId !== null && $sessionUserId !== $consumedUserId)`
 * behind a `?int $sessionUserId = null` parameter that the PUBLIC entry point
 * defaulted to null. So the exact call the package advertised —
 * `Passport::login($update)`, one argument — skipped the check, and with the
 * shipped `user_lookup=field` configuration the claim-matching resolver
 * ignored the id it was handed and resolved the row from the EMAIL CLAIM
 * instead. A user who had merely seen another user's nonce in a URL or a log
 * was logged in as that user.
 *
 * Every other test in this suite issues and spends the nonce from the SAME
 * session, which is precisely why the shipped fix was green. These tests are
 * the ones that could not have been.
 *
 * THE RULE UNDER TEST, exactly as implemented:
 *   a login is allowed ⟺ the id of the session presenting the update is a
 *   non-null int AND it equals the id the consumed nonce was issued to AND
 *   the identity seam resolves the same user from that id (a payload claim
 *   may confirm that user, never select one).
 */
final class PassportSessionBindingTest extends TestCase
{
    private string $pemFile = '';

    private string $publicPem = '';

    private string $privatePem = '';

    protected function getPackageProviders(mixed $app): array
    {
        return [PassportServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        BoundUser::$resolved = null;
        BoundUser::$lookups = [];

        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        self::assertNotFalse($key);
        $exported = openssl_pkey_export($key, $this->privatePem);
        self::assertTrue($exported);
        $details = openssl_pkey_get_details($key);
        self::assertIsArray($details);

        $this->pemFile = (string) tempnam(sys_get_temp_dir(), 'teleframe-passport-binding-');
        file_put_contents($this->pemFile, $this->privatePem);
        chmod($this->pemFile, 0o600);

        $this->publicPem = (string) $details['key'];
    }

    protected function tearDown(): void
    {
        if ($this->pemFile !== '' && is_file($this->pemFile)) {
            unlink($this->pemFile);
        }
        BoundUser::$resolved = null;
        BoundUser::$lookups = [];

        parent::tearDown();
    }

    // ── 1. the attack, verbatim, through the advertised public call ────

    /**
     * THE ATTACK. User A starts a Passport login and nonce N is issued for A.
     * N round-trips through Telegram, so it lands in URLs, referrer logs and
     * proxy logs. B — holding any valid session — then calls the call the
     * facade docblock advertised, `Passport::login($update)`, with A's
     * credentials and no session id.
     *
     * It is not refused by a domain check: it is unmakeable. The second
     * parameter is required and non-nullable, so the compiler refuses the call
     * and the IDE marks it, and NOTHING is written.
     */
    public function test_the_advertised_unbound_login_cannot_be_made_at_all(): void
    {
        $this->config();

        $nonces = new MemoryNonceRegistry(900);
        $writer = new MemoryBindingWriter;
        $this->service($nonces, $writer);

        // A starts the login: the nonce belongs to A (id 42).
        $nonce = $nonces->issue(42);
        $update = $this->updateWith($this->seal([
            'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
            'nonce' => $nonce,
        ]), fromId: 1724372757, passportData: ['email' => 'victim@example.test']);
        BoundUser::$resolved = BoundUser::forId(42);

        // B spends A's nonce through the PUBLIC entry point with no session id.
        // Pre-fix this was a silent, successful takeover.
        try {
            /** @phpstan-ignore-next-line intentionally unbound: the point of the test */
            Passport::login($update, null);
            self::fail('an unbound passport login must be impossible to express');
        } catch (\TypeError $e) {
            self::assertStringContainsString('sessionUserId', $e->getMessage());
        }

        // Nothing was written, and the nonce is still A's to spend.
        self::assertSame([], $writer->rows());
        self::assertSame(42, $nonces->consume($nonce));
    }

    /**
     * The same attack through the SECOND public entry point, handleUpdate().
     * That one keeps a nullable parameter so the bus can swallow updates with
     * no credentials at all — so a null there must be a LOUD refusal on an
     * update that does carry credentials, never a fall-through.
     */
    public function test_handle_update_with_credentials_and_no_session_is_refused_and_writes_nothing(): void
    {
        $this->config();

        $nonces = new MemoryNonceRegistry(900);
        $writer = new MemoryBindingWriter;
        $service = $this->service($nonces, $writer);
        $nonce = $nonces->issue(42);
        $update = $this->updateWith($this->seal([
            'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
            'nonce' => $nonce,
        ]), fromId: 1724372757, passportData: ['email' => 'victim@example.test']);
        BoundUser::$resolved = BoundUser::forId(42);

        try {
            $service->handleUpdate($update); // the attacker passes no session
            self::fail('an unbound passport login must be refused, not routed');
        } catch (PassportLoginException $e) {
            self::assertStringContainsString('no session is bound', $e->getMessage());
        }

        self::assertSame([], $writer->rows(), 'a refused login must not bind');
    }

    /**
     * The controller half: the one shipped call site that had a session id
     * available and could therefore pass a WRONG one. Refused, and nothing
     * written.
     */
    public function test_another_sessions_nonce_is_refused_with_the_chain_reached(): void
    {
        $this->config();

        $nonces = new MemoryNonceRegistry(900);
        $writer = new MemoryBindingWriter;
        $service = $this->service($nonces, $writer);
        $nonce = $nonces->issue(42); // A's nonce
        BoundUser::$resolved = BoundUser::forId(42);

        $update = $this->updateWith($this->seal([
            'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
            'nonce' => $nonce,
        ]), fromId: 1724372757, passportData: ['email' => 'victim@example.test']);

        try {
            // B, with their OWN valid session id, spends A's nonce.
            $service->login($update, sessionUserId: 7);
            self::fail('a nonce from another session must be refused');
        } catch (PassportLoginException $e) {
            self::assertStringContainsString('different session', $e->getMessage());
        }

        self::assertSame([], $writer->rows());
        self::assertSame([], BoundUser::$lookups, 'the users table is never even consulted');
    }

    // ── 2. the claim-matching configuration ────────────────────────────

    /**
     * The shipped `user_lookup=field` configuration, on the exact payload the
     * attack used. Pre-fix the resolver ignored the session id it was handed
     * and returned the row the EMAIL CLAIM named — A's row — so the login
     * succeeded. It must now refuse: the claim agrees with nobody the
     * presenter proved.
     */
    public function test_the_shipped_claim_matching_config_cannot_resolve_from_the_claim_alone(): void
    {
        $this->config(['user_lookup' => 'field', 'require_verified' => true]);

        $nonces = new MemoryNonceRegistry(900);
        $writer = new MemoryBindingWriter;
        $service = $this->service(
            $nonces,
            $writer,
            new FieldIdentityResolver(
                userClass: BoundUser::class,
                finder: fn (string $column, string $value): ?Model => BoundUser::query()
                    ->where($column, $value)
                    ->first(),
            ),
        );
        $nonce = $nonces->issue(42);
        BoundUser::$resolved = BoundUser::forId(42);

        $update = $this->updateWith($this->seal([
            'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
            'nonce' => $nonce,
        ]), fromId: 1724372757, passportData: [
            // A Telegram-VERIFIED email claim (§6 plaintext) naming account 42.
            'email' => 'victim@example.test',
        ]);

        // (a) unbound: handleUpdate refuses before the resolver is reached.
        try {
            $service->handleUpdate($update);
            self::fail('unbound must be refused');
        } catch (PassportLoginException) {
            // expected
        }
        self::assertSame([], $writer->rows());

        // (b) bound to the nonce's own owner: the claim CONFIRMS, and the
        //     legitimate flow still works.
        $result = $service->login($update, sessionUserId: 42);
        self::assertInstanceOf(LoginResult::class, $result);
        self::assertSame(42, (int) $result->user->getKey());
        self::assertCount(1, $writer->rows());
    }

    /**
     * The resolver seam on its own, which is where the takeover actually
     * happened: a claim that names a DIFFERENT account than the bound session
     * is refused even though the lookup found a real, claim-matching row.
     */
    public function test_a_claim_naming_another_account_is_refused(): void
    {
        $found = null;
        $resolver = new FieldIdentityResolver(
            finder: function (string $column, string $value) use (&$found): ?Model {
                $found = [$column, $value];

                // The claim matches a REAL row — account 99, not the bound 42.
                return BoundUser::forId(99);
            },
        );

        $resolved = $resolver->resolve(
            new PassportPayload(
                ['secure_data' => ['personal_details' => ['first_name' => 'Ada']], 'nonce' => 'n'],
                PassportElementFactory::claims(['email' => 'victim@example.test']),
            ),
            42,
        );

        self::assertNull($resolved, 'a claim may confirm the bound user, never select another one');
        self::assertSame(['email', 'victim@example.test'], $found, 'the lookup did run; the MATCH was refused');
    }

    public function test_a_claim_matching_the_bound_user_is_accepted(): void
    {
        $user = BoundUser::forId(42);
        $resolver = new FieldIdentityResolver(finder: fn (): ?Model => $user);

        $resolved = $resolver->resolve(
            new PassportPayload(
                ['secure_data' => ['personal_details' => ['first_name' => 'Ada']], 'nonce' => 'n'],
                PassportElementFactory::claims(['email' => 'mine@example.test']),
            ),
            42,
        );

        self::assertSame($user, $resolved, 'a claim that agrees with the bound session is evidence, not authority');
    }

    public function test_the_claim_matching_resolver_refuses_an_unbound_call_without_reaching_the_lookup(): void
    {
        $calls = 0;
        $resolver = new FieldIdentityResolver(
            finder: function () use (&$calls): ?Model {
                $calls++;

                return new BoundUser;
            },
        );

        $resolved = $resolver->resolve(
            new PassportPayload(
                ['secure_data' => ['personal_details' => ['first_name' => 'Ada']], 'nonce' => 'n'],
                PassportElementFactory::claims(['email' => 'victim@example.test']),
            ),
            null,
        );

        self::assertNull($resolved, 'no bound session means no user, whatever the claim says');
        self::assertSame(0, $calls, 'and the lookup must never be issued');
    }

    public function test_an_unidentifiable_row_is_refused_even_when_the_claim_matches(): void
    {
        // No key at all: an unidentifiable row cannot be shown to BE the bound
        // user, so it is not accepted as one.
        $resolver = new FieldIdentityResolver(finder: fn (): ?Model => new BoundUser); // no key

        self::assertNull($resolver->resolve(
            new PassportPayload(
                ['secure_data' => ['personal_details' => ['first_name' => 'Ada']], 'nonce' => 'n'],
                PassportElementFactory::claims(['email' => 'a@example.test']),
            ),
            42,
        ));
    }

    // ── 3. the legitimate flow ─────────────────────────────────────────

    public function test_the_same_session_issues_and_spends_the_nonce(): void
    {
        $this->config();

        $nonces = new MemoryNonceRegistry(900);
        $writer = new MemoryBindingWriter;
        $ridden = [];
        $service = $this->service(
            $nonces,
            $writer,
            onLogin: static function (Model $user) use (&$ridden): void {
                $ridden[] = (int) $user->getKey();
            },
        );
        $nonce = $service->issueNonce(42);
        BoundUser::$resolved = BoundUser::forId(42);

        $result = $service->login(
            $this->updateWith($this->seal([
                'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
                'nonce' => $nonce,
            ]), fromId: 1724372757),
            sessionUserId: 42,
        );

        self::assertInstanceOf(LoginResult::class, $result);
        self::assertSame(42, (int) $result->user->getKey());
        self::assertSame(1724372757, $result->tgUserId);
        self::assertSame([42], $ridden, 'the host auth ride ran exactly once, on the bound user');
        self::assertCount(1, $writer->rows());
    }

    // ── 4. the test that would have caught it last time ────────────────

    /**
     * A SIGNATURE is a security control here, so it is asserted like one.
     * Before the fix `login()` was `?int $sessionUserId = null`: optional,
     * nullable, defaulted. Every layer below — the docblock, the IDE, the
     * compiler — then advertised the unbound call, and the binding check it
     * guarded was skippable by construction.
     *
     * Revert `login()` to its old signature and this test fails on three
     * separate assertions before it even reaches a behavioural one.
     */
    public function test_no_public_entry_point_can_reach_the_identity_seam_with_an_unbound_session(): void
    {
        $login = new ReflectionMethod(PassportLoginService::class, 'login');
        $params = $login->getParameters();
        self::assertCount(2, $params);
        self::assertSame('update', $params[0]->getName());
        self::assertSame('sessionUserId', $params[1]->getName());

        // (a) REQUIRED — the call `Passport::login($update)` does not compile.
        self::assertFalse($params[1]->isOptional(), 'the session id must be required');
        self::assertFalse($params[1]->isDefaultValueAvailable(), 'and it must have no default to fall back to');

        // (b) NON-NULLABLE — `null` is a TypeError, not a silent skip.
        self::assertFalse($params[1]->allowsNull(), 'unknown is refuse, never "allow"');
        self::assertSame('int', (string) $params[1]->getType());

        // (c) the chain it delegates to agrees, so the private seam cannot be
        //     the soft one.
        $chain = new ReflectionMethod(PassportLoginService::class, 'chain');
        $chainParams = $chain->getParameters();
        self::assertFalse($chainParams[1]->allowsNull());
        self::assertFalse($chainParams[1]->isDefaultValueAvailable());

        // (d) the facade advertises the same shape to hosts, and does not
        //     document the unsafe one-argument call.
        $doc = (string) (new ReflectionClass(Passport::class))->getDocComment();
        self::assertStringContainsString('@method static LoginResult login(array $update, int $sessionUserId)', $doc);
        self::assertStringNotContainsString('login(array $update)', $doc, 'the one-argument call must not be advertised');
        self::assertStringNotContainsString('login(array $update, ?int', $doc, 'nor a nullable one');

        // (e) handleUpdate may take a null — for updates that carry no
        //     credentials at all — so the behaviour, not the signature, has
        //     to carry the refusal, which is pinned behaviourally in
        //     test_handle_update_with_credentials_and_no_session_is_refused…
        $handle = new ReflectionMethod(PassportLoginService::class, 'handleUpdate');
        self::assertTrue($handle->getParameters()[1]->allowsNull());
    }

    /**
     * A host that skipped the type system entirely — dynamic call, no static
     * analysis, no IDE — must still be refused LOUD, not fatally and not
     * quietly. `strict_types` is declared in this file, so a null argument
     * from a typed caller is a TypeError; the engine's own refusal is
     * PassportLoginException, and neither may write anything.
     */
    public function test_a_dynamic_unbound_call_writes_nothing_whatever_it_raises(): void
    {
        $this->config();

        $nonces = new MemoryNonceRegistry(900);
        $writer = new MemoryBindingWriter;
        $service = $this->service($nonces, $writer);
        $nonce = $nonces->issue(42);
        BoundUser::$resolved = BoundUser::forId(42);
        $update = $this->updateWith($this->seal([
            'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
            'nonce' => $nonce,
        ]), fromId: 1724372757, passportData: ['email' => 'victim@example.test']);

        // Exactly the pre-fix attack, run dynamically so no static signature
        // can be doing the work.
        $call = Closure::fromCallable([$service, 'login']);

        $raised = null;
        try {
            $call($update, null);
        } catch (\Throwable $e) {
            $raised = $e;
        }

        self::assertNotNull($raised, 'the unbound login must not succeed');
        self::assertInstanceOf(\TypeError::class, $raised);
        self::assertSame([], $writer->rows());
    }

    // ── helpers ───────────────────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $config
     */
    private function config(array $config = []): void
    {
        config()->set('teleframe.passport', array_merge([
            'enabled' => true,
            'private_key_path' => $this->pemFile,
            'account_id' => 7,
            'nonce_ttl' => 900,
            'nonce_store' => '',
            'allowed_types' => ['personal_details', 'email', 'phone'],
            'user_lookup' => 'session',
            'user_lookup_field' => 'email',
            'require_password_confirmation' => false,
            'require_verified' => false,
            'bot_id' => 0,
            'bot_username' => '',
            'scope' => ['personal_details', 'email', 'phone'],
            'public_key' => '',
            'success_redirect' => '',
        ], $config));
    }

    private function service(
        MemoryNonceRegistry $nonces,
        BindingWriter $writer,
        ?IdentityResolver $identity = null,
        ?\Closure $onLogin = null,
    ): PassportLoginService {
        return new PassportLoginService(
            config: new PassportConfig((array) config('teleframe.passport')),
            keys: new PassportKeyLoader($this->pemFile),
            nonces: $nonces,
            identity: $identity ?? new SessionIdentityResolver(BoundUser::class),
            writer: $writer,
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
     * @param  array{data: string, secret: string, hash: string}  $envelope
     * @return array<string, mixed>
     */
    private function updateWith(array $envelope, int $fromId, array $passportData = []): array
    {
        return [
            'update_id' => 1,
            'message' => [
                'from' => ['id' => $fromId],
                'passport_data' => PassportElementFactory::passportData($envelope, $passportData),
            ],
        ];
    }
}

/**
 * The bound user: a DB-free Eloquent Model, id 42, whose query surface records
 * every lookup so a test can assert whether the users table was consulted at
 * all.
 */
final class BoundUser extends Model
{
    public ?int $id = 42;

    public static ?self $resolved = null;

    /** A row as a real host's users table hands one back: keyed AND identified. */
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
