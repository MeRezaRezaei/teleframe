<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel\Http\Dashboard;

use Illuminate\Cache\ArrayStore;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use MeRezaRezaei\Teleframe\Laravel\Http\Controllers\Dashboard\DashboardRoutes;
use MeRezaRezaei\Teleframe\Laravel\Http\Controllers\Dashboard\PassportController;
use MeRezaRezaei\Teleframe\Laravel\PassportServiceProvider;
use MeRezaRezaei\Teleframe\Passport\BindingWriter;
use MeRezaRezaei\Teleframe\Passport\NonceRegistry;
use MeRezaRezaei\Teleframe\Passport\PassportLoginService;
use MeRezaRezaei\Teleframe\Passport\PassportSupport;
use MeRezaRezaei\Teleframe\Tests\Laravel\Http\Dashboard\Support\TestUser;
use MeRezaRezaei\Teleframe\Tests\Passport\SpecCredentialFactory;

/**
 * The Passport HTTP LOGIN surface end-to-end through the package's own
 * routes: start (issues the one-time nonce + the Telegram handshake) and
 * callback (feeds the returned credentials to the shipped chain). The
 * payload is sealed with REAL crypto against a throwaway RSA keypair, so
 * the engine's decryptor is exercised for real — only the binding writer is
 * the in-memory fake (the Eloquent writer has its own suite).
 *
 * The nonce is issued by `start` and consumed by `callback`, so "the callback
 * consumes the nonce exactly once" and "a replay is refused" are asserted
 * end-to-end, not against a hand-made update.
 */
final class PassportLoginFlowTest extends TestCase
{
    private string $pemFile = '';

    private string $publicPem = '';

    private string $privatePem = '';

    private RecordingBindingWriter $writer;

    private ?\Closure $successHook = null;

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

        $this->pemFile = (string) tempnam(sys_get_temp_dir(), 'teleframe-passport-http-');
        file_put_contents($this->pemFile, $this->privatePem);
        chmod($this->pemFile, 0o600);

        $bindings = require dirname(__DIR__, 4).'/src/Laravel/Migrations/2026_09_08_000100_create_tl_user_bindings_table.php';
        $bindings->up();

        // A host user table with the column the field-lookup mode compares.
        if (! Schema::hasColumn('users', 'phone')) {
            Schema::table('users', function ($table): void {
                $table->string('phone')->nullable();
            });
        }

        $this->writer = new RecordingBindingWriter;
        $this->app->instance(PassportSupport::WRITER_KEY, $this->writer);

        $this->passportConfig();
    }

    protected function tearDown(): void
    {
        if ($this->pemFile !== '' && is_file($this->pemFile)) {
            unlink($this->pemFile);
        }
        $this->successHook = null;

        parent::tearDown();
    }

    // ── start ────────────────────────────────────────────────────────

    public function test_start_refuses_when_passport_is_not_enabled(): void
    {
        $this->passportConfig(['enabled' => false]);
        DashboardRoutes::register();

        $user = $this->makeUser('off@example.test');

        $this->actingAs($user)
            ->postJson('/teleframe/passport/start')
            ->assertStatus(403)
            ->assertJsonPath('ok', false);

        // Nothing was issued: a disabled module can never mint a nonce.
        self::assertNull($this->nonces()->consume('irrelevant'));
    }

    public function test_start_requires_an_authenticated_session(): void
    {
        $this->passportConfig();
        DashboardRoutes::register();

        $this->postJson('/teleframe/passport/start')
            ->assertStatus(401);
    }

    public function test_start_issues_a_nonce_bound_to_the_authenticated_user(): void
    {
        $this->passportConfig([
            'bot_id' => 4242,
            'bot_username' => 'teleframe_bot',
            'public_key' => $this->publicPem,
        ]);
        DashboardRoutes::register();

        $user = $this->makeUser('alice@example.test');

        $res = $this->actingAs($user)->postJson('/teleframe/passport/start');
        $res->assertOk()->assertJsonPath('ok', true);

        $nonce = (string) $res->json('nonce');
        self::assertNotSame('', $nonce);

        // The nonce is bound to THIS session's user, one-time, and the
        // Telegram handshake carries it out of the package.
        self::assertSame((int) $user->getKey(), $this->nonces()->consume($nonce));
        // The `scope` Telegram is actually asked for: the spec's
        // `{"v":1,"data":[…]}` object, with `v` = 1. The two IDENTITY element
        // types ARE requested, under the Bot API's element-type names — the
        // phone is `phone_number`, which is why the host-facing `phone`
        // spelling is translated rather than forwarded. Not asking for them
        // means no `email` / `phone_number` ELEMENT ever arrives in
        // `PassportData.data`, so the §6 claim channel stays empty.
        self::assertSame(
            ['v' => 1, 'data' => ['personal_details', 'email', 'phone_number']],
            $res->json('request.scope'),
        );
        // The flat list is still offered, for the host's own display.
        self::assertSame(
            ['personal_details', 'email', 'phone'],
            $res->json('scope'),
        );
        self::assertSame($nonce, $res->json('request.nonce'));
        self::assertSame($this->publicPem, $res->json('request.public_key'));
        self::assertSame(4242, $res->json('request.bot_id'));
        self::assertSame('https://t.me/teleframe_bot?start='.$nonce, $res->json('telegram_url'));
        self::assertSame(route('teleframe.passport.callback'), $res->json('callback_url'));
    }

    public function test_start_omits_the_deep_link_when_no_bot_username_is_configured(): void
    {
        $this->passportConfig(['bot_username' => '']);
        DashboardRoutes::register();

        $res = $this->actingAs($this->makeUser('bob@example.test'))
            ->postJson('/teleframe/passport/start');

        $res->assertOk();
        self::assertNull($res->json('telegram_url'));
        self::assertSame('/passport '.$res->json('nonce'), $res->json('bot_command'));
    }

    // ── callback ─────────────────────────────────────────────────────

    public function test_callback_consumes_the_nonce_exactly_once_and_binds(): void
    {
        $this->passportConfig(['account_id' => 9]);
        DashboardRoutes::register();

        $user = $this->makeUser('carol@example.test');
        $nonce = (string) $this->actingAs($user)->postJson('/teleframe/passport/start')->json('nonce');

        $credentials = $this->seal([
            'secure_data' => [
                'personal_details' => ['first_name' => 'Ada', 'last_name' => 'Lovelace'],
            ],
            'nonce' => $nonce,
        ]);

        $res = $this->actingAs($user)->postJson('/teleframe/passport/callback', [
            'data' => $credentials['data'],
            'hash' => $credentials['hash'],
            'secret' => $credentials['secret'],
            'email' => 'ada@example.test',
            'from_id' => 1724372757,
        ]);

        if ($res->status() !== 200) {
            self::fail('callback: '.(string) $res->json('message'));
        }
        $res->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('tg_user_id', 1724372757)
            ->assertJsonPath('account_id', 9)
            ->assertJsonPath('fields_verified', ['personal_details', 'email']);

        // Bound exactly once, to the user the nonce was issued for.
        self::assertCount(1, $this->writer->writes);
        self::assertSame((int) $user->getKey(), (int) $this->writer->writes[0]['model']->getKey());
        self::assertSame(9, $this->writer->writes[0]['account_id']);

        // The nonce is gone — a second consume finds nothing.
        self::assertNull($this->nonces()->consume($nonce));
    }

    /**
     * THE SHAPE THE CONTROLLER ACTUALLY HAS TO REBUILD, through HTTP.
     *
     * The callbacks above post a bare `email` beside the credentials, which
     * this controller wraps into a correctly-typed element. Real Telegram
     * does not post that: it POSTs a `PassportData` object whose `data` array
     * holds `EncryptedPassportElement` entries, and the verified email is the
     * plaintext `email` field of the one typed `email` (§6). This is that
     * request, end to end, and it is the one that would have caught the
     * controller dropping the element list — which it did, and which made
     * email/phone login impossible through this seam no matter what Telegram
     * sent.
     */
    public function test_callback_accepts_a_real_nested_passport_data_object(): void
    {
        $this->passportConfig(['account_id' => 9]);
        DashboardRoutes::register();

        $user = $this->makeUser('carol@example.test');
        $nonce = (string) $this->actingAs($user)->postJson('/teleframe/passport/start')->json('nonce');

        $credentials = $this->seal([
            'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
            'nonce' => $nonce,
        ]);

        $res = $this->actingAs($user)->postJson('/teleframe/passport/callback', [
            'passport_data' => [
                'credentials' => $credentials,
                'data' => [
                    [
                        'type' => 'personal_details',
                        'data' => 'ZW5jcnlwdGVkLWVsZW1lbnQtZGF0YQ==',
                        'hash' => 'ZWxlbWVudC1oYXNo',
                    ],
                    ['type' => 'email', 'email' => 'ada@example.test', 'hash' => 'ZW1haWwtaGFzaA=='],
                ],
            ],
            'from_id' => 1724372757,
        ]);

        if ($res->status() !== 200) {
            self::fail('callback: '.(string) $res->json('message'));
        }
        $res->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('tg_user_id', 1724372757)
            ->assertJsonPath('fields_verified', ['personal_details', 'email']);

        self::assertCount(1, $this->writer->writes);
        self::assertSame((int) $user->getKey(), (int) $this->writer->writes[0]['model']->getKey());
    }

    /**
     * The same request with the verified email on an element of the WRONG
     * type. The element's `type` is what selects the field, so this yields no
     * claim and the field-mode cross-check refuses — loudly, naming the TYPE
     * and never the value. Otherwise anyone able to influence an element's
     * contents could get a claim read from a coordinate Telegram does not
     * vouch for.
     */
    public function test_callback_refuses_a_claim_on_an_element_of_another_type(): void
    {
        $this->passportConfig(['account_id' => 9, 'user_lookup' => 'field', 'require_verified' => true]);
        DashboardRoutes::register();

        $user = $this->makeUser('carol@example.test');
        $nonce = (string) $this->actingAs($user)->postJson('/teleframe/passport/start')->json('nonce');

        $credentials = $this->seal([
            'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
            'nonce' => $nonce,
        ]);

        $res = $this->actingAs($user)->postJson('/teleframe/passport/callback', [
            'passport_data' => [
                'credentials' => $credentials,
                'data' => [
                    [
                        'type' => 'personal_details',
                        'data' => 'ZW5jcnlwdGVkLWVsZW1lbnQtZGF0YQ==',
                        'hash' => 'ZWxlbWVudC1oYXNo',
                        'email' => 'attacker@example.test',
                    ],
                ],
            ],
            'from_id' => 1724372757,
        ]);

        $res->assertStatus(422)->assertJsonPath('ok', false);
        $message = (string) $res->json('message');
        self::assertStringNotContainsString('attacker@example.test', $message, 'never echo a value');
        self::assertCount(0, $this->writer->writes, 'and nothing is bound');
    }

    /**
     * THE ACCOUNT-TAKEOVER TEST.
     *
     * The nonce travels to Telegram and back, so it can appear in a URL, a
     * referrer log or a proxy log. It therefore proves only that the payload
     * is intact — NOT who is presenting it. Identity is resolved FROM the
     * nonce, so without an explicit check that the nonce belongs to the
     * session presenting it, any authenticated user who learns another user's
     * nonce can spend it and be logged in as them. The host-auth ride then
     * regenerates THEIR session onto THAT account.
     *
     * This was a real critical defect: the service threaded a $sessionUserId
     * argument all the way into chain() and then never read it, so the
     * binding existed in the signature and in the docblock and nowhere else.
     * Every other test in this file issues and spends the nonce from the same
     * session, so none of them could have caught it.
     */
    public function test_a_nonce_issued_to_one_session_cannot_be_spent_by_another(): void
    {
        $this->passportConfig();
        DashboardRoutes::register();

        $victim = $this->makeUser('victim@example.test');
        $attacker = $this->makeUser('attacker@example.test');

        // The VICTIM starts the login, so the nonce is bound to the victim.
        $nonce = (string) $this->actingAs($victim)
            ->postJson('/teleframe/passport/start')
            ->json('nonce');
        self::assertNotSame('', $nonce);

        $credentials = $this->seal([
            'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
            'nonce' => $nonce,
        ]);

        // The ATTACKER presents it from their own valid session.
        $res = $this->actingAs($attacker)->postJson('/teleframe/passport/callback', [
            'data' => $credentials['data'],
            'hash' => $credentials['hash'],
            'secret' => $credentials['secret'],
            'email' => 'victim@example.test',
            'from_id' => 1724372757,
        ]);

        $res->assertStatus(422)->assertJsonPath('ok', false);
        self::assertStringContainsString(
            'different session',
            (string) $res->json('message'),
        );

        // The decisive assertion: nothing was ever written. A takeover that
        // "succeeds quietly" is still a takeover.
        self::assertSame([], $this->writer->writes);
    }

    /**
     * THE SECOND TAKEOVER, over HTTP.
     *
     * The fix that shipped before this one was skippable: `login()` took an
     * OPTIONAL, NULLABLE session id, so the bound value could simply be left
     * out — and this endpoint's own docblock claimed that was not a bypass
     * because "a callback with no session is refused by the route's own auth
     * middleware". That is true here and irrelevant: the attack is a callback
     * WITH a session, just not the one the nonce belongs to, or none at all.
     *
     * The session is the only thing a nonce can be bound to, so a request
     * that cannot produce one is refused before the chain runs.
     */
    public function test_a_callback_with_no_session_is_refused_before_the_chain_runs(): void
    {
        $this->passportConfig();
        DashboardRoutes::register();

        $user = $this->makeUser('victim2@example.test');
        // Issued straight from the registry, so no actingAs() has run in this
        // test and the request below really is sessionless.
        $nonce = $this->nonces()->issue((int) $user->getKey());

        $credentials = $this->seal([
            'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
            'nonce' => $nonce,
        ]);

        // No actingAs: no authenticated session to bind the login to. The
        // route group's auth middleware answers this before the controller,
        // which is exactly the point — the controller does not rely on that.
        $res = $this->postJson('/teleframe/passport/callback', [
            'data' => $credentials['data'],
            'hash' => $credentials['hash'],
            'secret' => $credentials['secret'],
            'email' => 'victim2@example.test',
            'from_id' => 1724372757,
        ]);

        $res->assertStatus(401);
        self::assertSame([], $this->writer->writes);

        // The controller's OWN guard, independent of the route middleware —
        // because a host may mount the callback on its own route group, and
        // the docblock's "the auth middleware will catch it" reasoning is
        // exactly what left the previous hole open.
        $direct = app(PassportController::class)->callback(
            Request::create('/teleframe/passport/callback', 'POST'),
            $this->app->make(PassportLoginService::class),
        );
        self::assertSame(401, $direct->getStatusCode());
        self::assertSame([], $this->writer->writes);

        // And the victim's nonce survives: an unauthenticated request must not
        // be able to burn it either.
        self::assertSame((int) $user->getKey(), $this->nonces()->consume($nonce));
    }

    /**
     * The same attack through the SHIPPED claim-matching configuration, where
     * the resolver used to ignore the session it was handed and return the row
     * the email claim named. The claim here names the VICTIM's account while
     * the session presenting it belongs to the ATTACKER, so the claim and the
     * bound session disagree and the login is refused.
     */
    public function test_the_claim_matching_config_refuses_a_claim_naming_another_account(): void
    {
        $this->passportConfig([
            'user_lookup' => 'field',
            'require_verified' => true,
            'user_lookup_field' => 'email',
        ]);
        DashboardRoutes::register();

        $victim = $this->makeUser('victim3@example.test');
        $attacker = $this->makeUser('attacker3@example.test');

        $nonce = (string) $this->actingAs($victim)
            ->postJson('/teleframe/passport/start')
            ->json('nonce');

        $credentials = $this->seal([
            'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
            'nonce' => $nonce,
        ]);

        $res = $this->actingAs($attacker)->postJson('/teleframe/passport/callback', [
            'data' => $credentials['data'],
            'hash' => $credentials['hash'],
            'secret' => $credentials['secret'],
            'email' => 'victim3@example.test',
            'from_id' => 1724372757,
        ]);

        $res->assertStatus(422)->assertJsonPath('ok', false);
        self::assertSame([], $this->writer->writes, 'a claim that names another account must bind nothing');

        // The refused attempt still consumed the one-time nonce, so the
        // credentials cannot be re-presented by anyone afterwards either.
        self::assertNull($this->nonces()->consume($nonce));

        // And the legitimate field-mode flow is untouched: the victim starts a
        // FRESH login and the same claim confirms THEIR session.
        $fresh = (string) $this->actingAs($victim)
            ->postJson('/teleframe/passport/start')
            ->json('nonce');
        $freshCredentials = $this->seal([
            'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
            'nonce' => $fresh,
        ]);

        $this->actingAs($victim)->postJson('/teleframe/passport/callback', [
            'data' => $freshCredentials['data'],
            'hash' => $freshCredentials['hash'],
            'secret' => $freshCredentials['secret'],
            'email' => 'victim3@example.test',
            'from_id' => 1724372757,
        ])->assertOk();

        self::assertCount(1, $this->writer->writes);
        self::assertSame((int) $victim->getKey(), (int) $this->writer->writes[0]['model']->getKey());
        self::assertNotSame((int) $attacker->getKey(), (int) $this->writer->writes[0]['model']->getKey());
    }

    /** The same configuration, used the way it is meant to be: claim agrees. */
    public function test_the_claim_matching_config_still_admits_a_claim_that_agrees_with_the_session(): void
    {
        $this->passportConfig([
            'user_lookup' => 'field',
            'require_verified' => true,
            'user_lookup_field' => 'email',
        ]);
        DashboardRoutes::register();

        $user = $this->makeUser('zoe@example.test');
        $nonce = (string) $this->actingAs($user)
            ->postJson('/teleframe/passport/start')
            ->json('nonce');

        $credentials = $this->seal([
            'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
            'nonce' => $nonce,
        ]);

        $this->actingAs($user)->postJson('/teleframe/passport/callback', [
            'data' => $credentials['data'],
            'hash' => $credentials['hash'],
            'secret' => $credentials['secret'],
            'email' => 'zoe@example.test',
            'from_id' => 4242,
        ])->assertOk();

        self::assertCount(1, $this->writer->writes);
        self::assertSame((int) $user->getKey(), (int) $this->writer->writes[0]['model']->getKey());
    }

    public function test_replayed_callback_is_refused(): void
    {
        $this->passportConfig();
        DashboardRoutes::register();

        $user = $this->makeUser('dave@example.test');
        $nonce = (string) $this->actingAs($user)->postJson('/teleframe/passport/start')->json('nonce');

        $credentials = $this->seal([
            'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
            'nonce' => $nonce,
        ]);
        $payload = [
            'data' => $credentials['data'],
            'hash' => $credentials['hash'],
            'secret' => $credentials['secret'],
            'email' => 'ada@example.test',
            'from_id' => 1724372757,
        ];

        $this->actingAs($user)->postJson('/teleframe/passport/callback', $payload)->assertOk();

        // The replay is refused by the ENGINE's own one-time nonce guard, and
        // the message says so (nothing about the payload is echoed).
        $replay = $this->actingAs($user)->postJson('/teleframe/passport/callback', $payload);
        $replay->assertStatus(422)->assertJsonPath('ok', false);
        self::assertStringContainsString('nonce', (string) $replay->json('message'));

        // One login, one binding — the replay never reached the writer.
        self::assertCount(1, $this->writer->writes);
    }

    public function test_callback_falls_back_when_the_cache_store_cannot_lock(): void
    {
        $this->passportConfig();
        DashboardRoutes::register();

        $user = $this->makeUser('kate@example.test');
        $nonce = (string) $this->actingAs($user)->postJson('/teleframe/passport/start')->json('nonce');

        $credentials = $this->seal([
            'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
            'nonce' => $nonce,
        ]);
        $body = [
            'data' => $credentials['data'],
            'hash' => $credentials['hash'],
            'secret' => $credentials['secret'],
            'email' => 'ada@example.test',
            'from_id' => 4242,
        ];

        // Hold the exact lock key the callback uses, on a store that has no
        // lock support at all — the documented fallback must still complete
        // the login rather than turning a missing lock capability into an
        // outage. The one-time nonce is what actually guards the replay.
        $this->app->instance('cache', new class extends ArrayStore implements LockProvider
        {
            public function lock($name, $seconds = 0, $owner = null)
            {
                throw new \BadMethodCallException('locks are not supported by this store');
            }
        });
        Cache::clearResolvedInstances();

        $this->actingAs($user)->postJson('/teleframe/passport/callback', $body)->assertOk();
        self::assertCount(1, $this->writer->writes);

        // Still one-time: the same credentials a second time are refused.
        $this->actingAs($user)->postJson('/teleframe/passport/callback', $body)->assertStatus(422);
    }

    public function test_success_hook_fires_with_the_resolved_user(): void
    {
        $seen = [];
        $this->app->instance(
            PassportSupport::ON_LOGIN_KEY,
            static function (object $user) use (&$seen): void {
                $seen[] = $user;
            },
        );

        $this->passportConfig();
        DashboardRoutes::register();

        $user = $this->makeUser('erin@example.test');
        $nonce = (string) $this->actingAs($user)->postJson('/teleframe/passport/start')->json('nonce');

        $credentials = $this->seal([
            'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
            'nonce' => $nonce,
        ]);

        $this->actingAs($user)->postJson('/teleframe/passport/callback', [
            'data' => $credentials['data'],
            'hash' => $credentials['hash'],
            'secret' => $credentials['secret'],
            'email' => 'ada@example.test',
            'from_id' => 4242,
        ])->assertOk();

        self::assertCount(1, $seen);
        self::assertSame((int) $user->getKey(), (int) $seen[0]->getKey());
    }

    public function test_callback_refuses_a_contradictory_payload_via_the_field_resolver(): void
    {
        $this->passportConfig([
            'user_lookup' => 'field',
            'require_verified' => true,
            'user_lookup_field' => 'email',
        ]);
        DashboardRoutes::register();

        $user = $this->makeUser('frank@example.test');
        $nonce = (string) $this->actingAs($user)->postJson('/teleframe/passport/start')->json('nonce');

        // A vetted email AND a phone: the resolver refuses rather than
        // picking a winner (FieldIdentityResolver, ambiguity rule intact).
        $credentials = $this->seal([
            'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
            'nonce' => $nonce,
        ]);

        $this->actingAs($user)
            ->postJson('/teleframe/passport/callback', [
                'data' => $credentials['data'],
                'hash' => $credentials['hash'],
                'secret' => $credentials['secret'],
                'email' => 'ada@example.test',
                'phone_number' => '+15551234567',
                'from_id' => 4242,
            ])
            ->assertStatus(422)
            ->assertJsonPath('ok', false);

        self::assertSame([], $this->writer->writes);
    }

    public function test_callback_refuses_malformed_credentials_without_consuming_the_nonce(): void
    {
        $this->passportConfig();
        DashboardRoutes::register();

        $user = $this->makeUser('gina@example.test');
        $nonce = (string) $this->actingAs($user)->postJson('/teleframe/passport/start')->json('nonce');

        $this->actingAs($user)
            ->postJson('/teleframe/passport/callback', ['data' => 'only-data'])
            ->assertStatus(422)
            ->assertJsonPath('ok', false);

        // A malformed request must not burn a legitimate nonce.
        self::assertSame((int) $user->getKey(), $this->nonces()->consume($nonce));
    }

    public function test_callback_refuses_without_a_sender_telegram_id(): void
    {
        $this->passportConfig();
        DashboardRoutes::register();

        $user = $this->makeUser('hank@example.test');
        $nonce = (string) $this->actingAs($user)->postJson('/teleframe/passport/start')->json('nonce');

        $credentials = $this->seal([
            'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
            'nonce' => $nonce,
        ]);

        // sender_chat is refused: passport is user-only.
        $this->actingAs($user)->postJson('/teleframe/passport/callback', [
            'data' => $credentials['data'],
            'hash' => $credentials['hash'],
            'secret' => $credentials['secret'],
            'email' => 'ada@example.test',
            'from_id' => 0,
        ])->assertStatus(422);

        self::assertSame([], $this->writer->writes);
    }

    public function test_callback_redirects_when_a_success_redirect_is_configured(): void
    {
        $this->passportConfig(['success_redirect' => 'https://host.test/dashboard']);
        DashboardRoutes::register();

        $user = $this->makeUser('iris@example.test');
        $nonce = (string) $this->actingAs($user)->postJson('/teleframe/passport/start')->json('nonce');

        $credentials = $this->seal([
            'secure_data' => ['personal_details' => ['first_name' => 'Ada']],
            'nonce' => $nonce,
        ]);

        $this->actingAs($user)->post('/teleframe/passport/callback', [
            'data' => $credentials['data'],
            'hash' => $credentials['hash'],
            'secret' => $credentials['secret'],
            'email' => 'ada@example.test',
            'from_id' => 4242,
        ])->assertRedirect('https://host.test/dashboard');
    }

    public function test_the_page_renders_for_an_authenticated_user(): void
    {
        $this->passportConfig();
        DashboardRoutes::register();

        $this->actingAs($this->makeUser('jack@example.test'))
            ->get('/teleframe/passport')
            ->assertOk()
            ->assertSee('Telegram Passport')
            ->assertSee('Issue nonce');
    }

    // ── helpers ──────────────────────────────────────────────────────

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
            'bot_id' => 0,
            'bot_username' => '',
            'scope' => ['personal_details', 'email', 'phone'],
            'public_key' => '',
            'success_redirect' => '',
        ], $overrides));
    }

    private function nonces(): NonceRegistry
    {
        $nonces = $this->app->make(PassportSupport::NONCES_KEY);
        self::assertInstanceOf(NonceRegistry::class, $nonces);

        return $nonces;
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
 * A BindingWriter that records every write and returns a plain array — the
 * Eloquent writer has its own suite, so the HTTP flow only asserts THAT the
 * chain bound the resolved user, never how the row looks.
 */
final class RecordingBindingWriter implements BindingWriter
{
    /** @var list<array{model: mixed, account_id: int|null}> */
    public array $writes = [];

    public function bind(int $tgId, ?int $accountId, object $user): mixed
    {
        $this->writes[] = ['model' => $user, 'account_id' => $accountId];

        return ['tg_id' => $tgId, 'account_id' => $accountId];
    }
}
