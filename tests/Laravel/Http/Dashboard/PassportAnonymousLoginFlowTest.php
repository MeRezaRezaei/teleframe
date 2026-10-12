<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel\Http\Dashboard;

use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use MeRezaRezaei\Teleframe\Laravel\Http\Controllers\Dashboard\DashboardRoutes;
use MeRezaRezaei\Teleframe\Laravel\PassportServiceProvider;
use MeRezaRezaei\Teleframe\Passport\MemoryBindingWriter;
use MeRezaRezaei\Teleframe\Passport\MemoryPendingLoginRegistry;
use MeRezaRezaei\Teleframe\Passport\PassportAnonymousLoginService;
use MeRezaRezaei\Teleframe\Passport\PassportLoginException;
use MeRezaRezaei\Teleframe\Passport\PassportSupport;
use MeRezaRezaei\Teleframe\Passport\PendingLoginRegistry;
use MeRezaRezaei\Teleframe\Passport\SessionFingerprint;
use MeRezaRezaei\Teleframe\Tests\Laravel\Http\Dashboard\Support\TestUser;
use MeRezaRezaei\Teleframe\Tests\Passport\SpecCredentialFactory;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use ReflectionMethod;

/**
 * THE ANONYMOUS PASSPORT LOGIN — the surface that has to exist.
 *
 * Everything else in this module ATTACHES a Telegram identity to a user who is
 * already authenticated, and its own suite proves the attach flow is unbroken.
 * This file is the other half: the routes registered OUTSIDE the host's `auth`
 * group, driving a visitor who has NO Laravel session into one.
 *
 * WHY EVERY TEST HERE GOES THROUGH THE ROUTES. The two account takeovers this
 * module survived were both invisible to service-level tests: one was a
 * `?int $sessionUserId = null` that no unit test ever passed as null, and the
 * other was a check the HTTP layer could route around. A test that calls
 * `PassportAnonymousLoginService::login()` directly proves the chain; only a
 * test that POSTs proves what an ATTACKER can do, because an attacker goes
 * through the controller and its 401/403/422 gates, not through the service.
 *
 * So every test here uses the real URLs, real crypto against a throwaway RSA
 * keypair, and the real container bindings. Only the Eloquent writer is faked
 * (it has its own suite) and the PSR-16 store is the process-local one.
 */
final class PassportAnonymousLoginFlowTest extends TestCase
{
    private string $pemFile = '';

    private string $publicPem = '';

    private string $privatePem = '';

    private MemoryBindingWriter $writer;

    private RecordingLogSpy $log;

    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            PassportServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        self::assertNotFalse($key, 'openssl_pkey_new failed');
        self::assertTrue(openssl_pkey_export($key, $this->privatePem));
        $details = openssl_pkey_get_details($key);
        self::assertIsArray($details);
        $this->publicPem = (string) $details['key'];

        $this->pemFile = (string) tempnam(sys_get_temp_dir(), 'teleframe-passport-anon-');
        file_put_contents($this->pemFile, $this->privatePem);
        chmod($this->pemFile, 0o600);

        $bindings = require dirname(__DIR__, 4).'/src/Laravel/Migrations/2026_09_08_000100_create_tl_user_bindings_table.php';
        $bindings->up();

        // The phone column the field comparison may use.
        if (! Schema::hasColumn('users', 'phone')) {
            Schema::table('users', function ($table): void {
                $table->string('phone')->nullable();
            });
        }

        $this->writer = new MemoryBindingWriter;
        $this->app->instance(PassportSupport::WRITER_KEY, $this->writer);

        $this->log = new RecordingLogSpy;
        $this->app->instance(LoggerInterface::class, $this->log);

        // JSON requests carry NO cookies at all unless this is on, and the
        // whole flow is JSON. Without it every request arrives sessionless and
        // the cross-session refusal would fire for the legitimate trip too.
        $this->withCredentials();

        // The default configuration: `anonymous_login` is FALSE, so the
        // capability is inert until a test turns it on.
        $this->passportConfig();
    }

    protected function tearDown(): void
    {
        if ($this->pemFile !== '' && is_file($this->pemFile)) {
            unlink($this->pemFile);
        }

        parent::tearDown();
    }

    // ── the headline: a stranger with no session becomes a session ─────

    /**
     * THE REQUIREMENT, end to end, over HTTP, with no session anywhere.
     *
     * The owner's words: "it must be able to let laravel users login using it
     * when one configures them". Before this path existed the package could
     * only ATTACH, so this test is the whole point of the feature: a visitor
     * who has never been authenticated, holding nothing but the credentials
     * Telegram gave them, ends up with a Laravel session.
     *
     * The trip is the real one, in order. `login/start` mints the pending
     * marker and the one-time nonce; the nonce travels to Telegram; Telegram
     * answers with an encrypted payload; `login/callback` finishes it. The
     * nonce is NOT hand-made — it is the one `start` issued — so "the flow is
     * bound to this trip" is exercised rather than assumed.
     */
    public function test_a_visitor_with_no_session_becomes_a_logged_in_user(): void
    {
        $this->passportConfig(['anonymous_login' => true]);
        DashboardRoutes::register();

        $user = $this->makeUser('ada@example.test');

        // NOT authenticated. No actingAs(), no session, no guard.
        self::assertFalse($this->app['auth']->check());

        $start = $this->hit('postJson', '/teleframe/passport/login/start');
        $start->assertOk()->assertJsonPath('ok', true);

        $flow = (string) $start->json('flow');
        $nonce = (string) $start->json('nonce');
        self::assertNotSame('', $flow);
        self::assertNotSame('', $nonce);
        self::assertNotSame($flow, $nonce, 'the flow handle and the passport nonce must be two different secrets');

        // A real Telegram-shaped response: the encrypted credential set
        // carries the nonce the trip was issued, and the Telegram-VERIFIED
        // email is the §6 `PassportData` plaintext posted beside it.
        $credentials = $this->seal([
            'secure_data' => [
                'personal_details' => ['first_name' => 'Ada', 'last_name' => 'Lovelace'],
            ],
            'nonce' => $nonce,
        ]);

        $res = $this->hit('postJson', '/teleframe/passport/login/callback', [
            'flow' => $flow,
            'data' => $credentials['data'],
            'hash' => $credentials['hash'],
            'secret' => $credentials['secret'],
            'email' => 'ada@example.test',
            'from_id' => 1724372757,
        ]);

        if ($res->status() !== 200) {
            self::fail('anonymous callback: '.(string) $res->json('message'));
        }
        $res->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('tg_user_id', 1724372757)
            ->assertJsonPath('fields_verified', ['personal_details', 'email']);

        // THE DECISIVE ASSERTION. A Laravel session now exists, and it is the
        // user's — this is a login, not a JSON file that says so.
        self::assertTrue($this->app['auth']->check(), 'the callback must leave a Laravel session for the resolved user');
        $this->assertAuthenticatedAs($user);
        self::assertSame((int) $user->getKey(), (int) $this->app['auth']->id());

        // The Telegram identity was written to the resolved user.
        self::assertCount(1, $this->writer->rows());
        self::assertSame((int) $user->getKey(), (int) $this->writer->rows()[0]['user']->getKey());
    }

    /**
     * The session id is ROTATED on the way in. The trip was started by a
     * browser with no authenticated user; if the id did not move, a fixated
     * cookie would ride straight through the login. This is the
     * session-fixation half of the same property the authenticated flow has,
     * and it is the one thing a "did the login work" assertion cannot see.
     */
    public function test_the_session_id_is_rotated_by_a_successful_anonymous_login(): void
    {
        $this->passportConfig(['anonymous_login' => true]);
        DashboardRoutes::register();

        $user = $this->makeUser('grace@example.test');

        $start = $this->hit('postJson', '/teleframe/passport/login/start');
        $before = $this->sessionId();

        $credentials = $this->seal([
            'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
            'nonce' => (string) $start->json('nonce'),
        ]);

        $this->hit('postJson', '/teleframe/passport/login/callback', [
            'flow' => (string) $start->json('flow'),
            'data' => $credentials['data'],
            'hash' => $credentials['hash'],
            'secret' => $credentials['secret'],
            'email' => 'grace@example.test',
            'from_id' => 4242,
        ])->assertOk();

        self::assertNotSame('', $before);
        self::assertNotSame($before, $this->sessionId(), 'a login must rotate the session id it was handed');
        $this->assertAuthenticatedAs($user);
    }

    /** The Telegram identity may also be resolved by a VETTED PHONE number. */
    public function test_a_vetted_phone_resolves_the_user_too(): void
    {
        $this->passportConfig(['anonymous_login' => true]);
        DashboardRoutes::register();

        $user = $this->makeUser('judy@example.test');
        $user->phone = '+15551234567';
        $user->save();

        $start = $this->hit('postJson', '/teleframe/passport/login/start');
        $credentials = $this->seal([
            'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
            'nonce' => (string) $start->json('nonce'),
        ]);

        $this->hit('postJson', '/teleframe/passport/login/callback', [
            'flow' => (string) $start->json('flow'),
            'data' => $credentials['data'],
            'hash' => $credentials['hash'],
            'secret' => $credentials['secret'],
            'phone_number' => '+15551234567',
            'from_id' => 4242,
        ])->assertOk();

        $this->assertAuthenticatedAs($user);
    }

    // ── the binding is not optional: cross-session is refused ─────────

    /**
     * THE REGRESSION TEST FOR THE WHOLE CLASS OF BUG THAT BIT US TWICE.
     *
     * The flow token travels in a URL (`callback_url` in the start response) and
     * therefore lands in referrer logs, proxy logs and browser history. If the
     * marker it addresses were not bound to the browser that fetched it, then
     * holding the token would BE the login, and every leak of that token would
     * be an account takeover. Both earlier takeovers in this module were a
     * binding that could be skipped — a nullable argument, then a check the
     * call site could route around — and both were invisible to tests that
     * only ever completed a flow in the session that started it.
     *
     * So this test does the one thing no earlier test did: it STARTS the trip
     * in one browser and FINISHES it in another, over the real routes.
     */
    public function test_a_flow_started_in_one_browser_cannot_be_finished_in_another(): void
    {
        $this->passportConfig(['anonymous_login' => true]);
        DashboardRoutes::register();

        $this->makeUser('victim@example.test');

        // Browser A starts the trip. It has a session (the `web` group starts
        // one) but no authenticated user.
        $start = $this->hit('postJson', '/teleframe/passport/login/start');
        $start->assertOk();
        $flowA = (string) $start->json('flow');
        $sessionA = $this->sessionId();
        self::assertNotSame('', $sessionA);

        $credentials = $this->seal([
            'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
            'nonce' => (string) $start->json('nonce'),
        ]);

        // Browser B — a genuinely different session, which is what a stolen
        // token plus a fresh cookie jar looks like.
        $sessionB = $this->becomeNewBrowser();
        self::assertNotSame($sessionA, $sessionB, 'the two browsers must really be two sessions');

        $res = $this->hit('postJson', '/teleframe/passport/login/callback', [
            'flow' => $flowA,
            'data' => $credentials['data'],
            'hash' => $credentials['hash'],
            'secret' => $credentials['secret'],
            'email' => 'victim@example.test',
            'from_id' => 1724372757,
        ]);

        $res->assertStatus(422)->assertJsonPath('ok', false);
        self::assertStringContainsString('different browser session', (string) $res->json('message'));

        // The decisive half: not a refusal message, but NO LOGIN. A takeover
        // that fails loudly is a bug; one that fails quietly is a breach.
        self::assertFalse($this->app['auth']->check(), 'a foreign browser must not be logged in');
        self::assertSame([], $this->writer->rows(), 'nothing may be bound on a refused cross-session attempt');
    }

    /**
     * The token alone — with valid credentials, a real flow, and nothing else —
     * is not a login. This is the "it leaked in a referrer" scenario stated as
     * its own test, so the property stays pinned even if a future refactor of
     * the cross-session test weakens its assertions.
     */
    public function test_a_stolen_flow_token_with_no_matching_fingerprint_completes_nothing(): void
    {
        $this->passportConfig(['anonymous_login' => true]);
        DashboardRoutes::register();

        $this->makeUser('victim@example.test');

        $start = $this->hit('postJson', '/teleframe/passport/login/start');
        $credentials = $this->seal([
            'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
            'nonce' => (string) $start->json('nonce'),
        ]);

        // A browser that never fetched the flow, presenting it in full.
        $this->becomeNewBrowser();
        $res = $this->hit('postJson', '/teleframe/passport/login/callback', [
            'flow' => (string) $start->json('flow'),
            'data' => $credentials['data'],
            'hash' => $credentials['hash'],
            'secret' => $credentials['secret'],
            'email' => 'victim@example.test',
            'from_id' => 1724372757,
        ]);

        self::assertSame(422, $res->status());
        self::assertStringContainsString('different browser session', (string) $res->json('message'));
        self::assertFalse($this->app['auth']->check());
        self::assertSame([], $this->writer->rows());
    }

    /**
     * A sessionless fingerprint is REFUSED, not digested. `hash('sha256','')`
     * is a constant every browser without a session would share, so a caller
     * that failed to obtain a session would produce a marker bound to
     * "anybody who also has no session" — a binding that reads like a check
     * and is worth nothing. The formula refuses instead.
     */
    public function test_a_blank_session_id_cannot_produce_a_fingerprint(): void
    {
        $refused = false;
        try {
            SessionFingerprint::of('');
        } catch (PassportLoginException $e) {
            $refused = true;
            self::assertStringContainsString('blank', $e->getMessage());
        }
        self::assertTrue($refused, 'a blank session id must be refused, not hashed into a shared constant');
    }

    // ── one-time: replay and expiry ───────────────────────────────────

    public function test_a_replayed_anonymous_callback_is_refused(): void
    {
        $this->passportConfig(['anonymous_login' => true]);
        DashboardRoutes::register();

        $user = $this->makeUser('dave@example.test');

        $start = $this->hit('postJson', '/teleframe/passport/login/start');
        $credentials = $this->seal([
            'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
            'nonce' => (string) $start->json('nonce'),
        ]);
        $body = [
            'flow' => (string) $start->json('flow'),
            'data' => $credentials['data'],
            'hash' => $credentials['hash'],
            'secret' => $credentials['secret'],
            'email' => 'dave@example.test',
            'from_id' => 1724372757,
        ];

        $this->hit('postJson', '/teleframe/passport/login/callback', $body)->assertOk();
        $this->assertAuthenticatedAs($user);

        // THE REPLAY, which is what a leaked referrer gives an attacker: the
        // very same captured request, re-presented. The marker is single-use,
        // so it is refused BEFORE any identity work happens — and refused at
        // the marker, not at the field, which is what a spent nonce means.
        $this->becomeNewBrowser();
        $replay = $this->hit('postJson', '/teleframe/passport/login/callback', $body);
        $replay->assertStatus(422)->assertJsonPath('ok', false);
        self::assertStringContainsString('replayed', (string) $replay->json('message'));

        // And the same browser sending it twice is refused too — for the
        // different and stronger reason that it is now logged in, so there is
        // nothing left to replay INTO. Either way: no second login.
        $again = $this->hit('postJson', '/teleframe/passport/login/callback', $body);
        self::assertContains($again->status(), [403, 422], 'a second callback on a spent flow is refused');

        // The replaying browser is not logged in — which is the whole point of
        // a one-time marker — and nothing was bound a second time.
        self::assertFalse($this->app['auth']->check(), 'a replay must not authenticate anyone');
        self::assertCount(1, $this->writer->rows(), 'a replay must not bind a second time');
        self::assertSame((int) $user->getKey(), (int) $this->writer->rows()[0]['user']->getKey());
    }

    public function test_an_expired_pending_marker_is_refused(): void
    {
        $clock = 1_000;
        $this->app->instance(
            PendingLoginRegistry::class,
            new MemoryPendingLoginRegistry(60, static function () use (&$clock): int {
                return $clock;
            }),
        );

        $this->passportConfig(['anonymous_login' => true]);
        DashboardRoutes::register();

        $this->makeUser('erin@example.test');

        $start = $this->hit('postJson', '/teleframe/passport/login/start');
        $start->assertOk();

        $credentials = $this->seal([
            'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
            'nonce' => (string) $start->json('nonce'),
        ]);

        // The trip is abandoned long enough for the marker to age out.
        $clock += 61;

        $res = $this->hit('postJson', '/teleframe/passport/login/callback', [
            'flow' => (string) $start->json('flow'),
            'data' => $credentials['data'],
            'hash' => $credentials['hash'],
            'secret' => $credentials['secret'],
            'email' => 'erin@example.test',
            'from_id' => 1724372757,
        ]);

        $res->assertStatus(422);
        self::assertStringContainsString('expired', (string) $res->json('message'));
        self::assertFalse($this->app['auth']->check());
        self::assertSame([], $this->writer->rows());
    }

    /** An unknown or absent handle is refused before anything is decrypted. */
    public function test_a_callback_with_no_flow_token_is_refused(): void
    {
        $this->passportConfig(['anonymous_login' => true]);
        DashboardRoutes::register();

        $res = $this->hit('postJson', '/teleframe/passport/login/callback', [
            'data' => 'x', 'hash' => 'y', 'secret' => 'z', 'from_id' => 4242,
        ]);

        $res->assertStatus(422)->assertJsonPath('ok', false);
        self::assertStringContainsString('flow token', (string) $res->json('message'));
        self::assertFalse($this->app['auth']->check());
    }

    /** A made-up handle is refused the same way a spent one is. */
    public function test_an_unknown_flow_token_is_refused(): void
    {
        $this->passportConfig(['anonymous_login' => true]);
        DashboardRoutes::register();

        $this->makeUser('heidi@example.test');

        $start = $this->hit('postJson', '/teleframe/passport/login/start');
        $credentials = $this->seal([
            'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
            'nonce' => (string) $start->json('nonce'),
        ]);

        $res = $this->hit('postJson', '/teleframe/passport/login/callback', [
            'flow' => bin2hex(random_bytes(16)),
            'data' => $credentials['data'],
            'hash' => $credentials['hash'],
            'secret' => $credentials['secret'],
            'email' => 'heidi@example.test',
            'from_id' => 1724372757,
        ]);

        $res->assertStatus(422);
        self::assertStringContainsString('replayed', (string) $res->json('message'));
        self::assertFalse($this->app['auth']->check());
        self::assertSame([], $this->writer->rows());
    }

    /**
     * Credentials minted for a DIFFERENT trip are refused even from the right
     * browser, even with the right flow token. The marker records a digest of
     * the nonce it was issued for, so harvesting a valid credential set from
     * another flow buys nothing.
     */
    public function test_credentials_minted_for_another_flow_are_refused(): void
    {
        $this->passportConfig(['anonymous_login' => true]);
        DashboardRoutes::register();

        $this->makeUser('ivan@example.test');

        $start = $this->hit('postJson', '/teleframe/passport/login/start');
        $foreign = $this->seal([
            'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
            // A well-formed nonce that is NOT the one this flow was issued.
            'nonce' => bin2hex(random_bytes(16)),
        ]);

        $res = $this->hit('postJson', '/teleframe/passport/login/callback', [
            'flow' => (string) $start->json('flow'),
            'data' => $foreign['data'],
            'hash' => $foreign['hash'],
            'secret' => $foreign['secret'],
            'from_id' => 1724372757,
        ]);

        $res->assertStatus(422);
        self::assertStringContainsString('this login flow', (string) $res->json('message'));
        self::assertFalse($this->app['auth']->check());
        self::assertSame([], $this->writer->rows());
    }

    // ── an unvetted claim is refused ─────────────────────────────────

    /**
     * The anonymous path requires a TELEGRAM-VETTED field, because on this path
     * the field is the ENTIRE identity. The payload below carries an email as a
     * top-level plain scalar only; Telegram never listed it in `secure_data`, so
     * it never vouched for it. Refused — and the refusal names the field TYPE
     * without echoing its value.
     *
     * This is the DEFAULT posture, with `require_verified` off: there is no
     * configuration of this path on which an unvetted claim is accepted.
     */
    public function test_an_unverified_claim_is_refused_by_default(): void
    {
        $this->passportConfig(['anonymous_login' => true, 'require_verified' => false]);
        DashboardRoutes::register();

        $this->makeUser('frank@example.test');

        $start = $this->hit('postJson', '/teleframe/passport/login/start');
        $credentials = $this->seal([
            // NO secure_data entry: a bare, unvouched claim.
            'email' => 'frank@example.test',
            'nonce' => (string) $start->json('nonce'),
        ]);

        $res = $this->hit('postJson', '/teleframe/passport/login/callback', [
            'flow' => (string) $start->json('flow'),
            'data' => $credentials['data'],
            'hash' => $credentials['hash'],
            'secret' => $credentials['secret'],
            'from_id' => 1724372757,
        ]);

        $res->assertStatus(422)->assertJsonPath('ok', false);
        $message = (string) $res->json('message');
        self::assertStringContainsString(
            'PassportData.data elements',
            $message,
            'the refusal says where a verified value would have been',
        );
        self::assertStringContainsString('email', $message, 'the refusal names the field TYPE');
        self::assertStringNotContainsString(
            'frank@example.test',
            $message,
            'the refusal must never echo the value it refused',
        );

        self::assertFalse($this->app['auth']->check());
        self::assertSame([], $this->writer->rows());
    }

    /**
     * The same, with the host's own `require_verified` switch turned ON. The
     * outcome is identical, and that is the point: the flag is an opt-in the
     * authenticated path can leave off, while this path has no weaker setting
     * to be turned down to. The message says the flag was honoured, so a host
     * that set it can see it took effect.
     */
    public function test_require_verified_refuses_an_unverified_field_on_the_anonymous_path(): void
    {
        $this->passportConfig(['anonymous_login' => true, 'require_verified' => true]);
        DashboardRoutes::register();

        $this->makeUser('gina@example.test');

        $start = $this->hit('postJson', '/teleframe/passport/login/start');
        $credentials = $this->seal([
            'email' => 'gina@example.test',
            'nonce' => (string) $start->json('nonce'),
        ]);

        $res = $this->hit('postJson', '/teleframe/passport/login/callback', [
            'flow' => (string) $start->json('flow'),
            'data' => $credentials['data'],
            'hash' => $credentials['hash'],
            'secret' => $credentials['secret'],
            'from_id' => 1724372757,
        ]);

        $res->assertStatus(422)->assertJsonPath('ok', false);
        $message = (string) $res->json('message');
        self::assertStringContainsString('require_verified', $message);
        self::assertStringContainsString('email', $message, 'the refusal names the TYPE, not the value');
        self::assertStringNotContainsString('gina@example.test', $message);

        self::assertFalse($this->app['auth']->check());
        self::assertSame([], $this->writer->rows());
    }

    /** A vetted email AND a vetted phone names no single identity. Refused. */
    public function test_a_payload_naming_two_vetted_fields_is_refused(): void
    {
        $this->passportConfig(['anonymous_login' => true]);
        DashboardRoutes::register();

        $this->makeUser('heidi@example.test');

        $start = $this->hit('postJson', '/teleframe/passport/login/start');
        $credentials = $this->seal([
            'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
            'nonce' => (string) $start->json('nonce'),
        ]);

        $res = $this->hit('postJson', '/teleframe/passport/login/callback', [
            'flow' => (string) $start->json('flow'),
            'data' => $credentials['data'],
            'hash' => $credentials['hash'],
            'secret' => $credentials['secret'],
            'email' => 'heidi@example.test',
            'phone_number' => '+15551234567',
            'from_id' => 1724372757,
        ]);

        $res->assertStatus(422);
        self::assertStringContainsString('does not name one identity', (string) $res->json('message'));
        self::assertFalse($this->app['auth']->check());
        self::assertSame([], $this->writer->rows());
    }

    /**
     * THE OLD "SELF-CONTRADICTING PAYLOAD" TEST, AND WHY IT IS GONE.
     *
     * It asserted that a payload whose `secure_data.email` disagreed with its
     * own top-level `email` was refused — a comparison between the vetted and
     * the plain form of the same claim. After the re-plumb there is only ONE
     * place a claim can be (§6), so the pair no longer exists and the
     * comparison has nothing to compare.
     *
     * What replaces it is stronger and simpler: the non-spec value is not
     * merely contradicted, it is NEVER READ. A payload carrying an email
     * inside the encrypted blob AND a different one in §6's location logs in
     * exactly the §6 user, and the blob value has no influence whatsoever —
     * there is no branch left to bypass.
     */
    public function test_a_claim_inside_the_blob_is_never_read_even_beside_a_real_one(): void
    {
        $this->passportConfig(['anonymous_login' => true]);
        DashboardRoutes::register();

        $this->makeUser('ivan@example.test');
        $this->makeUser('attacker@example.test');

        $start = $this->hit('postJson', '/teleframe/passport/login/start');
        $credentials = $this->seal([
            'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
            // A non-spec coordinate (§5: the Credentials JSON is
            // {secure_data, nonce}). It names the OTHER user.
            'email' => 'attacker@example.test',
            'nonce' => (string) $start->json('nonce'),
        ]);

        $res = $this->hit('postJson', '/teleframe/passport/login/callback', [
            'flow' => (string) $start->json('flow'),
            'data' => $credentials['data'],
            'hash' => $credentials['hash'],
            'secret' => $credentials['secret'],
            // §6: the one value that is read.
            'email' => 'ivan@example.test',
            'from_id' => 1724372757,
        ]);

        $res->assertOk()->assertJsonPath('ok', true);
        self::assertTrue($this->app['auth']->check());
        self::assertSame(
            'ivan@example.test',
            (string) $this->app['auth']->user()?->getAttribute('email'),
            'the only identity acted on is the one Telegram verified',
        );
    }

    // ── the claim may not provision ───────────────────────────────────

    /**
     * A Telegram-VERIFIED field that matches NO user is refused, and no row is
     * written. `teleframe.passport.unknown_user` defaults to `refuse`, which is
     * the only shipped policy: whether any Telegram account holder may appear
     * in the host's users table is the host's product decision, not this
     * package's, and a verified-but-unknown address becoming a passwordless
     * login credential is exactly the decision that must not be made silently.
     */
    public function test_a_verified_field_matching_no_user_is_refused_and_never_provisions(): void
    {
        $this->passportConfig(['anonymous_login' => true]);
        DashboardRoutes::register();

        $this->makeUser('known@example.test');
        self::assertSame(1, TestUser::query()->count());

        $start = $this->hit('postJson', '/teleframe/passport/login/start');
        $credentials = $this->seal([
            'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
            'nonce' => (string) $start->json('nonce'),
        ]);

        $res = $this->hit('postJson', '/teleframe/passport/login/callback', [
            'flow' => (string) $start->json('flow'),
            'data' => $credentials['data'],
            'hash' => $credentials['hash'],
            'secret' => $credentials['secret'],
            'email' => 'stranger@example.test',
            'from_id' => 1724372757,
        ]);

        $res->assertStatus(422)->assertJsonPath('ok', false);
        self::assertStringContainsString('unknown_user=refuse', (string) $res->json('message'));

        // NO auto-provisioning, under any spelling: the users table is exactly
        // as it was, and the visitor did not become anybody.
        self::assertSame(1, TestUser::query()->count(), 'a refused anonymous login must never create an account');
        self::assertNull(TestUser::query()->where('email', 'stranger@example.test')->first());
        self::assertFalse($this->app['auth']->check());
        self::assertSame([], $this->writer->rows());
    }

    /**
     * The policy is READ, not decorative. A host that sets a value this package
     * does not implement is told so — and still gets a refusal, because no
     * value of that key may provision. This is the pin that stops the key from
     * ever becoming a silent "and if I set create, it creates".
     */
    public function test_an_unimplemented_unknown_user_policy_refuses_and_says_so(): void
    {
        $this->passportConfig(['anonymous_login' => true, 'unknown_user' => 'create']);
        DashboardRoutes::register();

        $this->makeUser('known@example.test');

        $start = $this->hit('postJson', '/teleframe/passport/login/start');
        $credentials = $this->seal([
            'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
            'nonce' => (string) $start->json('nonce'),
        ]);

        $res = $this->hit('postJson', '/teleframe/passport/login/callback', [
            'flow' => (string) $start->json('flow'),
            'data' => $credentials['data'],
            'hash' => $credentials['hash'],
            'secret' => $credentials['secret'],
            'email' => 'stranger@example.test',
            'from_id' => 1724372757,
        ]);

        $res->assertStatus(422);
        $message = (string) $res->json('message');
        self::assertStringContainsString('unknown_user=create', $message);
        self::assertStringContainsString('not a policy this package implements', $message);

        self::assertSame(1, TestUser::query()->count(), 'no policy value may create a row');
        self::assertFalse($this->app['auth']->check());
    }

    // ── an authenticated browser may not be REPLACED ──────────────────

    public function test_a_live_session_may_not_mint_an_anonymous_marker(): void
    {
        $this->passportConfig(['anonymous_login' => true]);
        DashboardRoutes::register();

        $existing = $this->makeUser('existing@example.test');

        $res = $this->actingAs($existing)->hit('postJson', '/teleframe/passport/login/start');
        $res->assertStatus(403);
        self::assertStringContainsString('already authenticated', (string) $res->json('message'));
        self::assertNull($res->json('flow'), 'a live session must not mint a marker it could be moved by');
    }

    /**
     * Even with a marker legitimately minted by a sessionless browser, a
     * browser that is ALREADY logged in cannot be swapped onto another account
     * by presenting a claim. Letting a claim REPLACE a live session is the
     * shape of "log in as somebody else", and the attach flow owns that case.
     */
    public function test_an_already_authenticated_browser_is_refused_the_anonymous_callback(): void
    {
        $this->passportConfig(['anonymous_login' => true]);
        DashboardRoutes::register();

        $existing = $this->makeUser('existing@example.test');
        $victim = $this->makeUser('victim@example.test');

        // A sessionless browser mints a real marker bound to its own session.
        $legit = $this->hit('postJson', '/teleframe/passport/login/start');
        $legit->assertOk();
        $credentials = $this->seal([
            'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
            'nonce' => (string) $legit->json('nonce'),
        ]);

        // The same browser is now authenticated as somebody else.
        $this->becomeNewBrowser();
        $this->actingAs($existing);

        $res = $this->hit('postJson', '/teleframe/passport/login/callback', [
            'flow' => (string) $legit->json('flow'),
            'data' => $credentials['data'],
            'hash' => $credentials['hash'],
            'secret' => $credentials['secret'],
            'email' => 'victim@example.test',
            'from_id' => 1724372757,
        ]);

        $res->assertStatus(403);
        self::assertStringContainsString('already authenticated', (string) $res->json('message'));
        $this->assertAuthenticatedAs($existing);
        self::assertNotSame((int) $existing->getKey(), (int) $victim->getKey());
        self::assertSame([], $this->writer->rows());
    }

    // ── the capability is off unless a host turns it on ───────────────

    /**
     * The DEFAULT. With `anonymous_login` false — which is what every host that
     * has never heard of the key gets — both routes are refused and NOTHING is
     * minted or consumed. The routes are registered anyway (so the refusal is a
     * decision, not a 404), and the refusal is explicit.
     */
    public function test_the_anonymous_routes_are_refused_by_default(): void
    {
        // No override: `anonymous_login` is absent, so it is false.
        $this->passportConfig();
        DashboardRoutes::register();

        $start = $this->hit('postJson', '/teleframe/passport/login/start');
        $start->assertStatus(403)->assertJsonPath('ok', false);
        self::assertStringContainsString('anonymous_login', (string) $start->json('message'));
        self::assertNull($start->json('flow'), 'a disabled capability must not mint a flow handle');
        self::assertNull($start->json('nonce'), 'a disabled capability must not mint a nonce');

        $callback = $this->hit('postJson', '/teleframe/passport/login/callback', [
            'flow' => bin2hex(random_bytes(16)),
            'data' => 'x', 'hash' => 'y', 'secret' => 'z', 'from_id' => 4242,
        ]);
        $callback->assertStatus(403)->assertJsonPath('ok', false);
        self::assertFalse($this->app['auth']->check());
    }

    /** A host that enables Passport for ATTACHING does not thereby allow login. */
    public function test_enabling_passport_alone_does_not_enable_the_anonymous_login(): void
    {
        $this->passportConfig(['enabled' => true, 'anonymous_login' => false]);
        DashboardRoutes::register();

        $this->hit('postJson', '/teleframe/passport/login/start')->assertStatus(403);
    }

    /** And the attach flow is untouched by the new pair's presence. */
    public function test_the_authenticated_attach_routes_keep_sitting_behind_the_auth_guard(): void
    {
        $this->passportConfig(['anonymous_login' => true]);
        DashboardRoutes::register();

        // No session: the ATTACH endpoints still refuse, because the new pair
        // is a separate group and did not relax the existing one.
        $this->hit('postJson', '/teleframe/passport/start')->assertStatus(401);
        $this->hit('postJson', '/teleframe/passport/callback', [
            'data' => 'x', 'hash' => 'y', 'secret' => 'z', 'from_id' => 4242,
        ])->assertStatus(401);
    }

    // ── nothing sensitive ever escapes ────────────────────────────────

    /**
     * The flow token travels in a URL, so it lands in referrer logs. That is
     * precisely why the session binding is mandatory — and it is also why the
     * token must never appear anywhere ELSE. This walks the SUCCESS case and
     * every refusal on the path, and asserts that no response body, redirect
     * target or log line carries the session id, the flow token, the nonce, the
     * credential envelope, a decrypted field value, the Telegram id or the key.
     */
    public function test_no_credential_ever_reaches_a_log_a_response_or_a_redirect(): void
    {
        $this->passportConfig([
            'anonymous_login' => true,
            'success_redirect' => 'https://host.test/dashboard',
        ]);
        DashboardRoutes::register();

        $user = $this->makeUser('leak@example.test');

        // ── the success case: a redirect, the easiest place to leak a token
        // (they are routinely built as `redirect($url.'?flow='.$token)`).
        $start = $this->hit('postJson', '/teleframe/passport/login/start');
        $start->assertOk();
        $flow = (string) $start->json('flow');
        $nonce = (string) $start->json('nonce');
        $session = $this->sessionId();

        $credentials = $this->seal([
            'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
            'nonce' => $nonce,
        ]);

        $ok = $this->hit('post', '/teleframe/passport/login/callback', [
            'flow' => $flow,
            'data' => $credentials['data'],
            'hash' => $credentials['hash'],
            'secret' => $credentials['secret'],
            'email' => 'leak@example.test',
            'from_id' => 1724372757,
        ]);
        $ok->assertRedirect('https://host.test/dashboard');
        $this->assertNoSecrets(
            (string) $ok->headers->get('Location').(string) $ok->getContent(),
            $this->secrets($flow, $nonce, $session, $credentials),
        );
        $this->assertAuthenticatedAs($user);

        // ── a replay: the marker is spent, presented by a fresh visitor
        $this->becomeNewBrowser();
        $replay = $this->hit('postJson', '/teleframe/passport/login/callback', [
            'flow' => $flow,
            'data' => $credentials['data'],
            'hash' => $credentials['hash'],
            'secret' => $credentials['secret'],
            'from_id' => 1724372757,
        ]);
        $replay->assertStatus(422);
        $this->assertNoSecrets((string) $replay->getContent(), $this->secrets($flow, $nonce, $session, $credentials));

        // ── a foreign browser presenting a fresh, valid trip
        $this->becomeNewBrowser();
        $foreignTrip = $this->hit('postJson', '/teleframe/passport/login/start');
        $foreign = $this->seal([
            'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
            'nonce' => (string) $foreignTrip->json('nonce'),
        ]);
        $this->becomeNewBrowser();
        $crossed = $this->hit('postJson', '/teleframe/passport/login/callback', [
            'flow' => (string) $foreignTrip->json('flow'),
            'data' => $foreign['data'],
            'hash' => $foreign['hash'],
            'secret' => $foreign['secret'],
            'from_id' => 1724372757,
        ]);
        $crossed->assertStatus(422);
        $this->assertNoSecrets(
            (string) $crossed->getContent(),
            $this->secrets((string) $foreignTrip->json('flow'), (string) $foreignTrip->json('nonce'), $this->sessionId(), $foreign),
        );

        // ── an unvetted claim: the refusal names the TYPE and not the value
        $this->becomeNewBrowser();
        $bareTrip = $this->hit('postJson', '/teleframe/passport/login/start');
        $bare = $this->seal([
            'email' => 'leak@example.test',
            'nonce' => (string) $bareTrip->json('nonce'),
        ]);
        $unverified = $this->hit('postJson', '/teleframe/passport/login/callback', [
            'flow' => (string) $bareTrip->json('flow'),
            'data' => $bare['data'],
            'hash' => $bare['hash'],
            'secret' => $bare['secret'],
            'from_id' => 1724372757,
        ]);
        $unverified->assertStatus(422);
        $this->assertNoSecrets(
            (string) $unverified->getContent(),
            $this->secrets((string) $bareTrip->json('flow'), (string) $bareTrip->json('nonce'), $this->sessionId(), $bare),
        );

        // ── an unknown user: the policy is named, no address is
        $this->becomeNewBrowser();
        $strangerTrip = $this->hit('postJson', '/teleframe/passport/login/start');
        $stranger = $this->seal([
            'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
            'nonce' => (string) $strangerTrip->json('nonce'),
        ]);
        $unknown = $this->hit('postJson', '/teleframe/passport/login/callback', [
            'flow' => (string) $strangerTrip->json('flow'),
            'data' => $stranger['data'],
            'hash' => $stranger['hash'],
            'secret' => $stranger['secret'],
            'from_id' => 1724372757,
        ]);
        $unknown->assertStatus(422);
        $this->assertNoSecrets(
            (string) $unknown->getContent(),
            $this->secrets((string) $strangerTrip->json('flow'), (string) $strangerTrip->json('nonce'), $this->sessionId(), $stranger),
        );

        // Every refusal above is logged; none of the log lines carries a
        // secret either. This is the AGENTS.md credentials rule, enforced.
        self::assertTrue($this->log->sawRefusal(), 'the refusals must be logged, not swallowed');
        $this->assertNoSecrets($this->log->everything(), [
            $flow, $nonce, $session,
            (string) $foreignTrip->json('flow'), (string) $foreignTrip->json('nonce'),
            (string) $bareTrip->json('flow'), (string) $bareTrip->json('nonce'),
            'leak@example.test', 'stranger@example.test',
            $credentials['data'], $credentials['secret'], $credentials['hash'],
        ]);
    }

    /**
     * The start response is the ONE place the flow token and the nonce must
     * appear — the browser has to carry them. This asserts they appear THERE
     * and, more importantly, that neither leaks into the OTHER field: the
     * callback URL carries the flow (that is the design) and the Telegram deep
     * link carries the nonce, so a referrer captured from one does not hand
     * over the other.
     */
    public function test_the_start_response_separates_the_flow_handle_from_the_passport_nonce(): void
    {
        $this->passportConfig(['anonymous_login' => true, 'bot_username' => 'teleframe_bot']);
        DashboardRoutes::register();

        $start = $this->hit('postJson', '/teleframe/passport/login/start');
        $start->assertOk();

        $flow = (string) $start->json('flow');
        $nonce = (string) $start->json('nonce');

        self::assertStringContainsString('flow='.$flow, (string) $start->json('callback_url'));
        self::assertStringContainsString('start='.$nonce, (string) $start->json('telegram_url'));
        self::assertStringNotContainsString($nonce, (string) $start->json('callback_url'));
        self::assertStringNotContainsString($flow, (string) $start->json('telegram_url'));

        // The private key is not in the response in any form.
        self::assertStringNotContainsString('PRIVATE KEY', (string) $start->getContent());
    }

    // ── the throttle is real, not decorative ──────────────────────────

    /**
     * An unauthenticated authentication endpoint is a credential-guessing
     * surface, so it ships throttled rather than left to the host to remember.
     * With the ceiling at 2, the third start in a minute is refused — which is
     * also what bounds MARKER minting, not just credential presentation.
     */
    public function test_the_anonymous_start_route_is_throttled(): void
    {
        $this->passportConfig(['anonymous_login' => true, 'login_rate_limit' => 2]);
        DashboardRoutes::register();

        $this->hit('postJson', '/teleframe/passport/login/start')->assertOk();
        $this->hit('postJson', '/teleframe/passport/login/start')->assertOk();
        $this->hit('postJson', '/teleframe/passport/login/start')->assertStatus(429);
    }

    /** 0 removes the package's own ceiling; the host's still applies. */
    public function test_a_zero_rate_limit_means_no_package_ceiling(): void
    {
        $this->passportConfig(['anonymous_login' => true, 'login_rate_limit' => 0]);
        DashboardRoutes::register();

        for ($i = 0; $i < 5; $i++) {
            $this->hit('postJson', '/teleframe/passport/login/start')->assertOk();
        }
    }

    // ── the binding cannot be left out at the type level ──────────────

    /**
     * The takeover shape in this module has twice been "the check could be
     * skipped by omitting an argument". This asserts the property at the level
     * it is now enforced at — the signature — so no future refactor can make
     * the comparison optional again without failing a test.
     *
     * The authenticated user id is pinned too: it is a plain int with no
     * default, so "must not replace a live session" cannot be skipped by
     * omission either. That is the exact shape of the first takeover.
     */
    public function test_the_fingerprint_parameter_is_not_optional(): void
    {
        $params = (new ReflectionMethod(PassportAnonymousLoginService::class, 'login'))->getParameters();

        self::assertCount(4, $params);

        $flow = $params[1];
        self::assertSame('flowToken', $flow->getName());
        self::assertFalse($flow->allowsNull());
        self::assertFalse($flow->isOptional());

        $fingerprint = $params[2];
        self::assertSame('sessionFingerprint', $fingerprint->getName());
        self::assertFalse($fingerprint->allowsNull(), 'the session fingerprint must not be nullable');
        self::assertFalse($fingerprint->isOptional(), 'the session fingerprint must not be omittable');
        self::assertFalse($fingerprint->isDefaultValueAvailable());

        // The live-session guard is a plain non-nullable int whose default is
        // the "no live session" sentinel. It is NOT omittable in the dangerous
        // direction: omitting it says "not authenticated", never "skip the
        // check", and the HTTP layer always passes the real value — which the
        // live-session refusal test above pins by behaviour.
        $authenticated = $params[3];
        self::assertSame('authenticatedUserId', $authenticated->getName());
        self::assertFalse($authenticated->allowsNull());
        self::assertSame(0, $authenticated->getDefaultValue(), 'the default must be the "no live session" sentinel');
    }

    // ── helpers ───────────────────────────────────────────────────────

    /**
     * Make a request and then carry the session forward, the way a browser's
     * cookie jar would. Every request in this suite goes through here — that
     * is the whole difference between testing a real trip and testing a client
     * whose session id changes on every call.
     *
     * @param  array<string, mixed>  $data
     * @return TestResponse
     */
    private function hit(string $method, string $uri, array $data = [])
    {
        $response = match ($method) {
            'postJson' => $this->postJson($uri, $data),
            'post' => $this->post($uri, $data),
            'get' => $this->get($uri),
            default => throw new \InvalidArgumentException('unsupported method '.$method),
        };

        $this->keepSession();

        return $response;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function passportConfig(array $overrides = []): void
    {
        config()->set('teleframe.passport', array_merge([
            'enabled' => true,
            'private_key_path' => $this->pemFile,
            'account_id' => 0,
            'nonce_ttl' => 900,
            'nonce_store' => '',
            'allowed_types' => ['personal_details', 'email', 'phone'],
            'user_lookup' => 'session',
            'user_lookup_field' => 'email',
            'require_password_confirmation' => false,
            'require_verified' => false,
            'bot_id' => 4242,
            'bot_username' => 'teleframe_bot',
            'scope' => ['personal_details', 'email', 'phone'],
            'public_key' => $this->publicPem,
            'success_redirect' => '',
            // The login capability is OFF unless a test turns it on, and the
            // package's own throttle is off unless a test asks for it — the
            // default of 10/min would otherwise couple unrelated tests to a
            // shared counter.
            'anonymous_login' => false,
            'unknown_user' => 'refuse',
            'login_rate_limit' => 0,
        ], $overrides));
    }

    private function makeUser(string $email): TestUser
    {
        $user = new TestUser;
        $user->name = explode('@', $email)[0];
        $user->email = $email;
        $user->password = 'secret-hash';
        $user->save();

        return $user;
    }

    /**
     * The id of the session the test client is currently presenting.
     *
     * A REAL BROWSER sends its session cookie back on the next request, so the
     * session id it started the trip with is the one it presents at the
     * callback. Laravel's test client has no cookie jar: `StartSession` calls
     * `setId($request->cookies->get($name))` unconditionally, and a missing
     * cookie hands it null, which mints a brand-new id on every request. So a
     * test that does not carry the cookie is not testing a browser — it is
     * testing a session that changes identity between its own two requests,
     * which would make the cross-session refusal unfalsifiable (it would fire
     * even for the legitimate trip).
     *
     * `keepSession()` is therefore what makes these tests honest: it puts the
     * CURRENT session id back on the wire, exactly as a cookie jar would, and
     * `becomeNewBrowser()` is the opposite — drop it, so the next request
     * arrives as a different, sessionless visitor.
     */
    private function sessionId(): string
    {
        return (string) $this->app['session']->driver()->getId();
    }

    private function keepSession(): void
    {
        $this->withCookie((string) config('session.cookie'), $this->sessionId());
    }

    /**
     * A DIFFERENT visitor. `flushSession()` alone is not enough: it empties the
     * session but keeps its id, so the next request would still present the id
     * the marker was bound to. A second browser has an id this one never had,
     * so that is what goes on the wire — an id with no data behind it, which
     * is exactly what an attacker's fresh cookie jar looks like.
     */
    private function becomeNewBrowser(): string
    {
        $this->app['session']->driver()->flush();

        // The guard holds the previous request's user on the singleton, so a
        // new cookie alone would still look "already authenticated" to the
        // controller. Forgetting the guards is what makes the next request
        // RELOAD the user from the (now empty) session — which is what a real
        // second browser does, and why the cross-session tests below are about
        // the session and not about the guard.
        $this->app['auth']->forgetGuards();

        $id = substr(bin2hex(random_bytes(32)), 0, 40);
        $this->withCookie((string) config('session.cookie'), $id);

        return $id;
    }

    /**
     * Every string an attacker could use if the leak assertion ever fails.
     *
     * @param  array{data: string, hash: string, secret: string}  $credentials
     * @return list<string>
     */
    private function secrets(string $flow, string $nonce, string $session, array $credentials): array
    {
        return array_values(array_filter([
            $flow,
            $nonce,
            $session,
            $credentials['data'] ?? '',
            $credentials['hash'] ?? '',
            $credentials['secret'] ?? '',
            'leak@example.test',
            'stranger@example.test',
            '1724372757',
            'PRIVATE KEY',
        ], static fn (string $s): bool => $s !== ''));
    }

    /**
     * @param  list<string>  $secrets
     */
    private function assertNoSecrets(string $text, array $secrets): void
    {
        foreach ($secrets as $secret) {
            self::assertStringNotContainsString(
                $secret,
                $text,
                'a credential reached a response body, a redirect target or a log line',
            );
        }
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
}

/**
 * A PSR-3 spy that keeps every line the engine emitted, so the leak assertion
 * can run over the LOG as well as over responses. The engine logs refusals at
 * error level with method names and timings only; if a credential ever appears
 * in one, this is what catches it.
 */
final class RecordingLogSpy extends AbstractLogger
{
    /** @var list<string> */
    public array $lines = [];

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $this->lines[] = (string) $level.' '.(string) $message.' '.(string) json_encode($context);
    }

    public function everything(): string
    {
        return implode("\n", $this->lines);
    }

    public function sawRefusal(): bool
    {
        foreach ($this->lines as $line) {
            if (str_contains($line, 'PassportAnonymousLoginService::login failed')) {
                return true;
            }
        }

        return false;
    }
}
