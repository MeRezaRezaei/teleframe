<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel\Http\Dashboard;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use MeRezaRezaei\Teleframe\Laravel\Http\Controllers\Dashboard\DashboardRoutes;
use MeRezaRezaei\Teleframe\Laravel\Services\RelayTicketBroker;
use MeRezaRezaei\Teleframe\Tests\Laravel\Http\Dashboard\Support\TestUser;
use MeRezaRezaei\Teleframe\Vault\TelegramAccount;
use MeRezaRezaei\Teleframe\Vault\TelegramApp;

/**
 * Control-layer ticket seam: mint is tenant-scoped + requires a session,
 * redeem is single-use and never leaks the session string, and the account
 * index advertises live_endpoint only when configured.
 */
final class RelayTicketApiTest extends TestCase
{
    public function test_mint_returns_short_lived_ticket_without_session(): void
    {
        DashboardRoutes::register();

        $user = $this->makeUser('a@example.test');
        $account = $this->makeAccount($user, 'mine', 'session-string-a');

        $response = $this->actingAs($user)
            ->postJson("/teleframe/accounts/{$account->getKey()}/ticket")
            ->assertCreated()
            ->assertJsonPath('account_id', (int) $account->getKey())
            ->assertJsonPath('live_endpoint', null)
            ->assertJsonStructure(['ticket', 'expires_at']);

        $ticket = (string) $response->json('ticket');
        self::assertGreaterThan(20, strlen($ticket));
        self::assertStringNotContainsString('session-string-a', (string) $response->getContent());
    }

    public function test_mint_with_configured_endpoint_advertises_it(): void
    {
        config()->set('teleframe.telegram_web.live_endpoint', 'wss://relay.example.test/ws');
        DashboardRoutes::register();

        $user = $this->makeUser('a@example.test');
        $account = $this->makeAccount($user, 'mine', 'session-string-a');

        $this->actingAs($user)
            ->postJson("/teleframe/accounts/{$account->getKey()}/ticket")
            ->assertCreated()
            ->assertJsonPath('live_endpoint', 'wss://relay.example.test/ws');

        $this->actingAs($user)
            ->getJson('/teleframe/accounts')
            ->assertOk()
            ->assertJsonPath('live_endpoint', 'wss://relay.example.test/ws');
    }

    public function test_mint_is_tenant_scoped(): void
    {
        DashboardRoutes::register();

        $alice = $this->makeUser('alice@example.test');
        $bob = $this->makeUser('bob@example.test');
        $account = $this->makeAccount($alice, 'alice-acc', 'session-string-a');

        $this->actingAs($bob)
            ->postJson("/teleframe/accounts/{$account->getKey()}/ticket")
            ->assertNotFound();
    }

    public function test_mint_requires_existing_session(): void
    {
        DashboardRoutes::register();

        $user = $this->makeUser('a@example.test');
        $account = $this->makeAccount($user, 'no-session', null);

        $this->actingAs($user)
            ->postJson("/teleframe/accounts/{$account->getKey()}/ticket")
            ->assertStatus(422);
    }

    public function test_mint_binds_the_acting_laravel_user_into_the_ticket(): void
    {
        DashboardRoutes::register();

        $user = $this->makeUser('a@example.test');
        $account = $this->makeAccount($user, 'mine', 'session-string-a');

        $ticket = (string) $this->actingAs($user)
            ->postJson("/teleframe/accounts/{$account->getKey()}/ticket")
            ->assertCreated()
            ->json('ticket');

        $grant = app(RelayTicketBroker::class)->redeemIdentity($ticket);

        self::assertSame(
            (int) $user->getKey(),
            $grant['user_id'] ?? null,
            'the mint route binds the acting user identity into the ticket',
        );
        self::assertSame((int) $account->getKey(), $grant['account_id'] ?? null);
    }

    public function test_redeem_returns_account_and_is_single_use(): void
    {
        DashboardRoutes::register();

        $user = $this->makeUser('a@example.test');
        $account = $this->makeAccount($user, 'mine', 'session-string-a');

        $ticket = (string) $this->actingAs($user)
            ->postJson("/teleframe/accounts/{$account->getKey()}/ticket")
            ->assertCreated()
            ->json('ticket');

        $this->postJson('/teleframe/relay/redeem', ['ticket' => $ticket])
            ->assertOk()
            ->assertJsonPath('account_id', (int) $account->getKey())
            ->assertJsonPath('dc_id', 2)
            ->assertJsonPath('ok', true);

        $this->postJson('/teleframe/relay/redeem', ['ticket' => $ticket])
            ->assertStatus(410);
    }

    public function test_redeem_rejects_unknown_and_burned_tickets(): void
    {
        DashboardRoutes::register();

        $user = $this->makeUser('a@example.test');
        $this->actingAs($user);

        $this->postJson('/teleframe/relay/redeem', ['ticket' => 'no-such-ticket-anywhere'])
            ->assertStatus(410);

        $account = $this->makeAccount($user, 'mine', 'session-string-a');
        $broker = app(RelayTicketBroker::class);
        $minted = $broker->mint((int) $account->getKey());

        $this->postJson('/teleframe/relay/redeem', ['ticket' => $minted['ticket']])->assertOk();
        $this->postJson('/teleframe/relay/redeem', ['ticket' => $minted['ticket']])->assertStatus(410);
    }

    public function test_redeem_after_cache_expiry_fails(): void
    {
        DashboardRoutes::register();

        $user = $this->makeUser('a@example.test');
        $this->actingAs($user);

        $account = $this->makeAccount($user, 'mine', 'session-string-a');
        $broker = app(RelayTicketBroker::class);
        $minted = $broker->mint((int) $account->getKey());

        // Simulate TTL expiry without waiting: drop the cached entry.
        Cache::forget('telegram_relay_ticket:'.$minted['ticket']);

        $this->postJson('/teleframe/relay/redeem', ['ticket' => $minted['ticket']])
            ->assertStatus(410);
    }

    public function test_routes_are_registered(): void
    {
        DashboardRoutes::register();

        self::assertTrue(Route::has('teleframe.accounts.ticket'));
        self::assertTrue(Route::has('teleframe.relay.redeem'));
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
