<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel\Http\Dashboard;

use MeRezaRezaei\Teleframe\Laravel\Http\Controllers\Dashboard\DashboardRoutes;
use MeRezaRezaei\Teleframe\Tests\Laravel\Http\Dashboard\Support\TestUser;
use MeRezaRezaei\Teleframe\Vault\TelegramAccount;
use MeRezaRezaei\Teleframe\Vault\TelegramApp;

/**
 * Tenant-scoped apps + accounts over the dashboard JSON API. A second,
 * "other" user must never see or mutate the first user's rows.
 */
final class VaultApiTest extends TestCase
{
    public function test_apps_are_tenant_scoped_and_api_hash_is_masked(): void
    {
        DashboardRoutes::register();

        $user = $this->makeUser('a@example.test');
        $app = $this->makeApp($user, 'mine', 111, '0123456789abcdef');

        $this->actingAs($user)->getJson('/teleframe/apps')
            ->assertOk()
            ->assertJsonPath('apps.0.label', 'mine')
            ->assertJsonPath('apps.0.api_id', 111)
            ->assertJsonPath('apps.0.api_hash', '0123••••••••cdef')
            ->assertJsonPath('apps.0.api_hash', fn (string $v): bool => ! str_contains($v, '0123456789abcdef'));
    }

    public function test_apps_are_isolated_between_users(): void
    {
        DashboardRoutes::register();

        $alice = $this->makeUser('alice@example.test');
        $bob = $this->makeUser('bob@example.test');
        $this->makeApp($alice, 'alice-app', 111, '0123456789abcdef');

        $this->actingAs($bob)
            ->getJson('/teleframe/apps')
            ->assertOk()
            ->assertJsonCount(0, 'apps');

        $this->actingAs($bob)
            ->deleteJson('/teleframe/apps/1')
            ->assertNotFound();

        self::assertSame(1, TelegramApp::query()->count(), 'alice app must survive bob delete');
    }

    public function test_store_app_assigns_authenticated_owner(): void
    {
        DashboardRoutes::register();

        $alice = $this->makeUser('alice@example.test');
        $bob = $this->makeUser('bob@example.test');

        $this->actingAs($alice)->postJson('/teleframe/apps', [
            'label' => 'shared-label',
            'api_id' => 222,
            'api_hash' => 'abcdef0123456789',
        ])->assertCreated();

        // label is globally unique at the DB layer: bob cannot reuse it.
        $this->actingAs($bob)->postJson('/teleframe/apps', [
            'label' => 'shared-label',
            'api_id' => 333,
            'api_hash' => '0123456789abcdef',
        ])->assertStatus(422);
    }

    public function test_accounts_are_tenant_scoped(): void
    {
        DashboardRoutes::register();

        $alice = $this->makeUser('alice@example.test');
        $bob = $this->makeUser('bob@example.test');
        $app = $this->makeApp($alice, 'mine', 111, '0123456789abcdef');
        $account = new TelegramAccount;
        $account->app_id = (int) $app->getKey();
        $account->label = 'alice-acc';
        $account->type = TelegramAccount::TYPE_USER;
        $account->owner_type = $alice::class;
        $account->owner_id = (int) $alice->getKey();
        $account->session = 'placeholder-session';
        $account->dc_id = 2;
        $account->save();

        $this->actingAs($alice)
            ->getJson('/teleframe/accounts')
            ->assertOk()
            ->assertJsonCount(1, 'accounts')
            ->assertJsonPath('accounts.0.label', 'alice-acc')
            ->assertJsonPath('accounts.0.has_session', true);

        $this->actingAs($bob)
            ->getJson('/teleframe/accounts')
            ->assertOk()
            ->assertJsonCount(0, 'accounts');

        $this->actingAs($bob)
            ->deleteJson('/teleframe/accounts/1')
            ->assertNotFound();
    }

    public function test_dashboard_index_renders(): void
    {
        DashboardRoutes::register();

        $user = $this->makeUser('a@example.test');

        $this->actingAs($user)
            ->get('/teleframe')
            ->assertOk()
            ->assertSee('Teleframe', false)
            ->assertSee('Link Telegram account', false);
    }

    public function test_dashboard_requires_auth(): void
    {
        DashboardRoutes::register();

        $this->getJson('/teleframe/apps')->assertUnauthorized();
    }

    private function makeUser(string $email): TestUser
    {
        return TestUser::query()->create([
            'name' => 'Test',
            'email' => $email,
            'password' => 'secret',
        ]);
    }

    private function makeApp(TestUser $user, string $label, int $apiId, string $apiHash): TelegramApp
    {
        $app = new TelegramApp;
        $app->label = $label;
        $app->api_id = $apiId;
        $app->api_hash = $apiHash;
        $app->owner_type = $user::class;
        $app->owner_id = (int) $user->getKey();
        $app->save();

        return $app;
    }
}
