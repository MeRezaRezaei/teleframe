<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel\Http\Dashboard;

use MeRezaRezaei\Teleframe\Core\Exceptions\TelegramException;
use MeRezaRezaei\Teleframe\Core\MTProto\SessionData;
use MeRezaRezaei\Teleframe\Laravel\Http\Controllers\Dashboard\DashboardRoutes;
use MeRezaRezaei\Teleframe\Laravel\Services\TeleframeAuthService;
use MeRezaRezaei\Teleframe\Tests\Laravel\Http\Dashboard\Support\TestUser;
use MeRezaRezaei\Teleframe\Vault\TelegramAccount;
use MeRezaRezaei\Teleframe\Vault\TelegramApp;
use Mockery\MockInterface;

/**
 * End-to-end phone-login flow through the package controllers.
 * TeleframeAuthService is mocked; the session is synthetic so no real
 * MTProto call is made. Tests the three-actor flow: start → verify →
 * (2FA branch) → password → finalize, and the cache-TTL expiry path.
 */
final class TelegramLoginFlowTest extends TestCase
{
    private MockInterface $auth;

    protected function setUp(): void
    {
        parent::setUp();
        $this->auth = \Mockery::mock(TeleframeAuthService::class)->makePartial();
        $this->app->instance(TeleframeAuthService::class, $this->auth);

        // TeleframeClient::user() requires non-empty API credentials from config.
        config()->set('teleframe.api_id', 12345678);
        config()->set('teleframe.api_hash', 'abcdef0123456789');
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    public function test_full_flow_start_then_verify_then_2fa_then_password(): void
    {
        DashboardRoutes::register();

        $user = $this->makeUser('alice@example.test');
        $app = $this->makeApp($user, 'us', 12345678, 'abcdef0123456789');

        // --- start ---
        $sessionData = new SessionData(dcId: 2, authKey: str_repeat("\x00", 256), userId: 1724372757);
        $this->auth->shouldReceive('sendPhoneCode')
            ->once()
            ->andReturn([
                'phone_code_hash' => 'abc123',
                'session' => $sessionData,
            ]);

        $startRes = $this->actingAs($user)
            ->postJson('/teleframe/telegram/start', [
                'app_id' => (int) $app->getKey(),
                'phone' => '+15551234567',
                'dc_id' => 2,
                'label' => 'ana',
            ]);

        $startRes->assertOk()->assertJsonPath('message', fn (string $m): bool => str_contains($m, 'Verification'));
        $loginId = $startRes->json('login_id');
        self::assertNotEmpty($loginId);

        // --- verify (2FA branch) ---
        $this->auth->shouldReceive('signInWithCode')
            ->once()
            ->andThrow(new TelegramException('PHONE_CODE_INVALID SESSION_PASSWORD_NEEDED'));

        $verifyRes = $this->actingAs($user)
            ->postJson('/teleframe/telegram/verify', [
                'login_id' => $loginId,
                'code' => '12345',
            ]);

        $verifyRes->assertOk()->assertJsonPath('two_factor_required', true);

        // --- password (finalize) ---
        $this->auth->shouldReceive('check2faPassword')
            ->once()
            ->andReturn([]);

        $passRes = $this->actingAs($user)
            ->postJson('/teleframe/telegram/password', [
                'login_id' => $loginId,
                'password' => 'cloud-pass',
            ]);

        $passRes->assertCreated()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('account.user_id', 1724372757)
            ->assertJsonPath('account.has_session', true);

        // Verify persisted
        $account = TelegramAccount::query()->where('user_id', 1724372757)->first();
        self::assertNotNull($account);
        self::assertSame($user::class, $account->owner_type);
        self::assertSame((int) $user->getKey(), $account->owner_id);
        self::assertSame((int) $app->getKey(), $account->app_id);
        self::assertSame('ana', $account->label);
    }

    public function test_expired_cache_returns_410(): void
    {
        DashboardRoutes::register();

        $this->actingAs($this->makeUser('a@test.com'))
            ->postJson('/teleframe/telegram/verify', [
                'login_id' => 'nonexistent-uuid',
                'code' => '12345',
            ])
            ->assertStatus(410);
    }

    public function test_unique_label_deduplication(): void
    {
        DashboardRoutes::register();

        $user = $this->makeUser('a@test.com');
        $app = $this->makeApp($user, 'us', 12345678, 'abcdef0123456789');

        // Create a pre-existing account with label 'ana'
        $existing = new TelegramAccount;
        $existing->app_id = (int) $app->getKey();
        $existing->label = 'ana';
        $existing->type = TelegramAccount::TYPE_USER;
        $existing->owner_type = $user::class;
        $existing->owner_id = (int) $user->getKey();
        $existing->session = str_repeat("\x00", 64);
        $existing->dc_id = 2;
        $existing->save();

        $sessionData = new SessionData(dcId: 2, authKey: str_repeat("\x00", 256), userId: 999);
        $this->auth->shouldReceive('sendPhoneCode')->andReturn([
            'phone_code_hash' => 'h',
            'session' => $sessionData,
        ]);

        $startRes = $this->actingAs($user)
            ->postJson('/teleframe/telegram/start', [
                'app_id' => (int) $app->getKey(),
                'phone' => '+15551234567',
                'label' => 'ana',
            ]);
        $loginId = $startRes->json('login_id');

        $this->auth->shouldReceive('signInWithCode')->andReturn([]);
        $this->auth->shouldReceive('check2faPassword')->andReturn([]);

        $passRes = $this->actingAs($user)
            ->postJson('/teleframe/telegram/password', [
                'login_id' => $loginId,
                'password' => 'x',
            ]);

        $passRes->assertCreated()->assertJsonPath('account.label', 'ana #2');
    }

    // ── helpers ─────────────────────────────────────────────────────

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
