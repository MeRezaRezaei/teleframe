<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel\Http\Dashboard;

use Illuminate\Support\Facades\Route;
use MeRezaRezaei\Teleframe\Handler\InMemoryCache;
use MeRezaRezaei\Teleframe\Laravel\Http\Controllers\Dashboard\DashboardRoutes;
use MeRezaRezaei\Teleframe\Laravel\Services\RelayActiveAccount;
use MeRezaRezaei\Teleframe\Laravel\Services\RelayFactory;
use MeRezaRezaei\Teleframe\Laravel\Services\RelaySessionResolver;
use MeRezaRezaei\Teleframe\Tests\Laravel\Http\Dashboard\Support\TestUser;
use MeRezaRezaei\Teleframe\Vault\TelegramAccount;
use MeRezaRezaei\Teleframe\Vault\TelegramApp;
use RuntimeException;

/**
 * Control-layer account switch (Wave E): tenant-scoped like the ticket /
 * presence seams, renders the target account ACTIVE for the relay via the
 * tenant-namespaced relay:active:account:<owner> key (W-09), and stays fully
 * lazy — the switch request never builds a client or dials (its resolver
 * factory throws if called). live_endpoint is only advertised when
 * configured.
 */
final class RelaySwitchTest extends TestCase
{
    /** @var int how often the injected resolver's client factory ran during a request */
    private int $clientCalls = 0;

    /** @var int how often the injected resolver's vault loader ran during a request */
    private int $vaultLoaderCalls = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(
            RelayActiveAccount::class,
            new RelayActiveAccount(
                new InMemoryCache,
                new RelayFactory(new RelaySessionResolver(
                    vaultLoader: function (string $ref, int $dc): ?string {
                        $this->vaultLoaderCalls++;

                        return null;
                    },
                    clientFactory: function (string $session, int $dc): object {
                        $this->clientCalls++;

                        throw new RuntimeException('relay must never dial during a switch request');
                    },
                )),
            ),
        );
    }

    public function test_switch_requires_auth(): void
    {
        DashboardRoutes::register();

        $this->postJson('/teleframe/accounts/1/switch')->assertUnauthorized();
    }

    public function test_switch_is_tenant_scoped(): void
    {
        DashboardRoutes::register();

        $alice = $this->makeUser('alice@example.test');
        $bob = $this->makeUser('bob@example.test');
        $account = $this->makeAccount($alice, 'alice-acc', 'session-string-a');

        $this->actingAs($bob)
            ->postJson("/teleframe/accounts/{$account->getKey()}/switch")
            ->assertNotFound();
    }

    public function test_switch_renders_the_target_account_active_and_stays_lazy(): void
    {
        DashboardRoutes::register();

        $user = $this->makeUser('a@example.test');
        $account = $this->makeAccount($user, 'mine', 'session-string-a');

        $this->actingAs($user)
            ->postJson("/teleframe/accounts/{$account->getKey()}/switch")
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('account_id', (int) $account->getKey())
            ->assertJsonPath('live_endpoint', null);

        $current = app(RelayActiveAccount::class)->currentFor($this->scopeId($user));
        self::assertNotNull($current);
        self::assertSame((int) $account->getKey(), $current->accountId);
        self::assertSame(2, $current->dcId);
        self::assertSame('mine', $current->label);
        self::assertSame(0, $this->clientCalls, 'the switch must not build a client / dial');
        self::assertSame(0, $this->vaultLoaderCalls, 'the switch must not read the vault');
        self::assertNull(
            app(RelayActiveAccount::class)->current(),
            'W-09: a tenant switch must not write the shared GLOBAL active context',
        );
    }

    public function test_switching_one_tenant_does_not_repoint_another_tenant(): void
    {
        DashboardRoutes::register();

        $alice = $this->makeUser('alice@example.test');
        $bob = $this->makeUser('bob@example.test');
        $aliceAccount = $this->makeAccount($alice, 'alice-acc', 'session-string-a');
        $bobAccount = $this->makeAccount($bob, 'bob-acc', 'session-string-b');

        // Bob switches his own account first.
        $this->actingAs($bob)
            ->postJson("/teleframe/accounts/{$bobAccount->getKey()}/switch")
            ->assertOk();

        self::assertSame(
            (int) $bobAccount->getKey(),
            app(RelayActiveAccount::class)->currentFor($this->scopeId($bob))?->accountId,
        );

        // Alice then switches hers — Bob's context must be untouched (W-09).
        $this->actingAs($alice)
            ->postJson("/teleframe/accounts/{$aliceAccount->getKey()}/switch")
            ->assertOk();

        self::assertSame(
            (int) $aliceAccount->getKey(),
            app(RelayActiveAccount::class)->currentFor($this->scopeId($alice))?->accountId,
            'Alice renders her own account ACTIVE within her tenant scope',
        );
        self::assertSame(
            (int) $bobAccount->getKey(),
            app(RelayActiveAccount::class)->currentFor($this->scopeId($bob))?->accountId,
            'W-09: switching tenant A must not re-point tenant B',
        );
    }

    public function test_switch_advertises_live_endpoint_when_configured(): void
    {
        config()->set('teleframe.telegram_web.live_endpoint', 'wss://relay.example/apiws');
        DashboardRoutes::register();

        $user = $this->makeUser('a@example.test');
        $account = $this->makeAccount($user, 'mine', 'session-string-a');

        $this->actingAs($user)
            ->postJson("/teleframe/accounts/{$account->getKey()}/switch")
            ->assertOk()
            ->assertJsonPath('account_id', (int) $account->getKey())
            ->assertJsonPath('live_endpoint', 'wss://relay.example/apiws');
    }

    public function test_switch_requires_existing_session(): void
    {
        DashboardRoutes::register();

        $user = $this->makeUser('a@example.test');
        $account = $this->makeAccount($user, 'no-session', null);

        $this->actingAs($user)
            ->postJson("/teleframe/accounts/{$account->getKey()}/switch")
            ->assertStatus(422);
    }

    public function test_routes_are_registered(): void
    {
        DashboardRoutes::register();

        self::assertTrue(Route::has('teleframe.accounts.switch'));
    }

    private function makeUser(string $email): TestUser
    {
        return TestUser::query()->create([
            'name' => 'Test',
            'email' => $email,
            'password' => 'secret',
        ]);
    }

    /** Mirrors RelaySwitchController::scopeId() — the tenant namespace (W-09). */
    private function scopeId(TestUser $user): string
    {
        return $user::class.'#'.$user->getKey();
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
