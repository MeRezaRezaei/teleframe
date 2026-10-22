<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel\Http\Dashboard;

use Illuminate\Support\Facades\Route;
use MeRezaRezaei\Teleframe\Control\RelaySession;
use MeRezaRezaei\Teleframe\Control\RelaySessionRegistry;
use MeRezaRezaei\Teleframe\Laravel\Http\Controllers\Dashboard\DashboardRoutes;
use MeRezaRezaei\Teleframe\Realtime\CentrifugoBridge;
use MeRezaRezaei\Teleframe\Realtime\ChannelNames;
use MeRezaRezaei\Teleframe\Realtime\PresenceFeed;
use MeRezaRezaei\Teleframe\Tests\Laravel\Http\Dashboard\Support\TestUser;
use MeRezaRezaei\Teleframe\Vault\TelegramAccount;
use MeRezaRezaei\Teleframe\Vault\TelegramApp;

/**
 * Control-layer presence surface: tenant-scoped like the ticket seam,
 * `online` reflects the injected relay registry (a fresh array store per
 * test), touch heartbeats an active session or opens a `manual` one,
 * live_endpoint is only advertised when provisioned, and every show/touch
 * also fans the current presence out to `account:{id}:updates` (Wave E)
 * through the injected fake Centrifugo bridge.
 */
final class RelayPresenceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $registry = new RelaySessionRegistry(cache()->store('array'));
        $this->app->instance(RelaySessionRegistry::class, $registry);

        $bridge = new FakeCentrifugoBridge;
        $this->app->instance(CentrifugoBridge::class, $bridge);
        $this->app->instance(
            PresenceFeed::class,
            new PresenceFeed($bridge, $registry),
        );
    }

    public function test_presence_requires_auth(): void
    {
        DashboardRoutes::register();

        $this->getJson('/teleframe/accounts/1/presence')->assertUnauthorized();
    }

    public function test_presence_is_tenant_scoped(): void
    {
        DashboardRoutes::register();

        $alice = $this->makeUser('alice@example.test');
        $bob = $this->makeUser('bob@example.test');
        $account = $this->makeAccount($alice, 'alice-acc', 'session-string-a');

        $this->actingAs($bob)
            ->getJson("/teleframe/accounts/{$account->getKey()}/presence")
            ->assertNotFound();

        $this->actingAs($bob)
            ->postJson("/teleframe/accounts/{$account->getKey()}/presence/touch")
            ->assertNotFound();
    }

    public function test_presence_is_offline_without_session(): void
    {
        DashboardRoutes::register();

        $user = $this->makeUser('a@example.test');
        $account = $this->makeAccount($user, 'mine', 'session-string-a');

        $this->actingAs($user)
            ->getJson("/teleframe/accounts/{$account->getKey()}/presence")
            ->assertOk()
            ->assertJsonPath('account_id', (int) $account->getKey())
            ->assertJsonPath('online', false)
            ->assertJsonMissingPath('live_endpoint');
    }

    public function test_touch_starts_manual_session_and_presence_goes_online(): void
    {
        DashboardRoutes::register();

        $user = $this->makeUser('a@example.test');
        $account = $this->makeAccount($user, 'mine', 'session-string-a');

        $this->actingAs($user)
            ->postJson("/teleframe/accounts/{$account->getKey()}/presence/touch")
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->actingAs($user)
            ->getJson("/teleframe/accounts/{$account->getKey()}/presence")
            ->assertOk()
            ->assertJsonPath('online', true);

        $session = app(RelaySessionRegistry::class)->status((int) $account->getKey());
        self::assertNotNull($session);
        self::assertSame('manual', $session->relayId);
    }

    public function test_touch_heartbeats_existing_relay_session(): void
    {
        DashboardRoutes::register();

        $user = $this->makeUser('a@example.test');
        $account = $this->makeAccount($user, 'mine', 'session-string-a');
        $accountId = (int) $account->getKey();

        app(RelaySessionRegistry::class)->write($accountId, new RelaySession($accountId, 'relay-1', 100, 100));
        $before = app(RelaySessionRegistry::class)->status($accountId);
        self::assertNotNull($before);
        self::assertSame('relay-1', $before->relayId);
        self::assertSame(100, $before->lastSeenAt);

        $this->actingAs($user)
            ->postJson("/teleframe/accounts/{$accountId}/presence/touch")
            ->assertOk()
            ->assertJsonPath('ok', true);

        /** @var RelaySession|null $after */
        $after = app(RelaySessionRegistry::class)->status($accountId);
        self::assertNotNull($after);
        self::assertSame('relay-1', $after->relayId, 'touch must keep the session relay id');
        self::assertGreaterThan($before->lastSeenAt, $after->lastSeenAt, 'touch must advance lastSeenAt');
    }

    public function test_presence_advertises_live_endpoint_when_configured(): void
    {
        config()->set('teleframe.telegram_web.live_endpoint', 'wss://relay.example/apiws');
        DashboardRoutes::register();

        $user = $this->makeUser('a@example.test');
        $account = $this->makeAccount($user, 'mine', 'session-string-a');

        $this->actingAs($user)
            ->getJson("/teleframe/accounts/{$account->getKey()}/presence")
            ->assertOk()
            ->assertJsonPath('account_id', (int) $account->getKey())
            ->assertJsonPath('online', false)
            ->assertJsonPath('live_endpoint', 'wss://relay.example/apiws');
    }

    public function test_malformed_presence_payload_is_treated_as_offline(): void
    {
        DashboardRoutes::register();

        $user = $this->makeUser('a@example.test');
        $account = $this->makeAccount($user, 'mine', 'session-string-a');
        $accountId = (int) $account->getKey();

        $registry = app(RelaySessionRegistry::class);
        $registry->start($accountId, 'relay-1');
        cache()->store('array')->put('relay:session:'.$accountId, 'not-json{{{');
        self::assertNull($registry->status($accountId));

        $this->actingAs($user)
            ->getJson("/teleframe/accounts/{$accountId}/presence")
            ->assertOk()
            ->assertJsonPath('online', false);
    }

    public function test_touch_announces_presence_onto_the_updates_channel(): void
    {
        DashboardRoutes::register();

        $user = $this->makeUser('a@example.test');
        $account = $this->makeAccount($user, 'mine', 'session-string-a');
        $accountId = (int) $account->getKey();

        $this->actingAs($user)
            ->postJson("/teleframe/accounts/{$accountId}/presence/touch")
            ->assertOk()
            ->assertJsonPath('ok', true);

        $bridge = $this->fakeBridge();
        self::assertCount(1, $bridge->published, 'touch must fan the updated presence out');
        self::assertSame(ChannelNames::accountUpdates($accountId), $bridge->published[0][0]);
        self::assertSame($accountId, $bridge->published[0][1]['account_id']);
        self::assertTrue($bridge->published[0][1]['online'], 'after a manual touch the account is online');
    }

    public function test_show_announces_offline_presence_when_no_session(): void
    {
        DashboardRoutes::register();

        $user = $this->makeUser('a@example.test');
        $account = $this->makeAccount($user, 'mine', 'session-string-a');
        $accountId = (int) $account->getKey();

        $this->actingAs($user)
            ->getJson("/teleframe/accounts/{$accountId}/presence")
            ->assertOk()
            ->assertJsonPath('online', false);

        $bridge = $this->fakeBridge();
        self::assertCount(1, $bridge->published, 'show must announce the current status too');
        self::assertSame(ChannelNames::accountUpdates($accountId), $bridge->published[0][0]);
        self::assertFalse($bridge->published[0][1]['online'], 'no session = offline on the feed');
    }

    public function test_routes_are_registered(): void
    {
        DashboardRoutes::register();

        self::assertTrue(Route::has('teleframe.accounts.presence'));
        self::assertTrue(Route::has('teleframe.accounts.presence.touch'));
    }

    private function fakeBridge(): FakeCentrifugoBridge
    {
        $bridge = app(CentrifugoBridge::class);

        if (! $bridge instanceof FakeCentrifugoBridge) {
            self::fail('expected the test FakeCentrifugoBridge to be bound');
        }

        return $bridge;
    }

    private function makeUser(string $email): TestUser
    {
        return TestUser::query()->create([
            'name' => 'Test',
            'email' => $email,
            'password' => 'secret',
        ]);
    }

    private function makeAccount(TestUser $user, string $label, ?string $session): TelegramAccount
    {
        $app = new TelegramApp;
        $app->label = $label.'-app';
        $app->api_id = 111;
        $app->api_hash = '0123456789abcdef';
        $app->owner_type = $user::class;
        $app->owner_id = (int) $user->getKey();
        $app->save();

        $account = new TelegramAccount;
        $account->app_id = (int) $app->getKey();
        $account->label = $label;
        $account->type = TelegramAccount::TYPE_USER;
        $account->owner_type = $user::class;
        $account->owner_id = (int) $user->getKey();
        $account->session = $session;
        $account->dc_id = 2;
        $account->save();

        return $account;
    }
}

/**
 * Centrifugo bridge spy: records every publish as [channel, payload] so a
 * PresenceFeed run can assert the channel shape and online/offline state
 * without a live realtime server.
 */
final class FakeCentrifugoBridge implements CentrifugoBridge
{
    /** @var list<array{0: string, 1: array<string, mixed>}> */
    public array $published = [];

    /** @param array<string, mixed> $payload */
    public function publish(string $channel, array $payload): void
    {
        $this->published[] = [$channel, $payload];
    }
}
