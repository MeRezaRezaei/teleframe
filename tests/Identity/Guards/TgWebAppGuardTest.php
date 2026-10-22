<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Identity\Guards;

use Illuminate\Http\Request;
use MeRezaRezaei\Teleframe\Identity\Bindings;
use MeRezaRezaei\Teleframe\Identity\Guards\TgWebAppGuard;
use MeRezaRezaei\Teleframe\Tests\Identity\Support\TestContactableUser;
use MeRezaRezaei\Teleframe\Tests\Identity\TestCase;

/**
 * Q10 `tg-webapp` guard: reuses VerifyMiniAppInitData by instantiation,
 * adds FRESHNESS (auth_date within miniapp_auth_max_age) and Q7/Q8 binding
 * resolution. Forged / stale / absent-auth_date / unbound identities are
 * all rejected to null (guest) — never throw.
 */
class TgWebAppGuardTest extends TestCase
{
    private const TOKEN = '123456:TESTBOTTOKEN';

    protected function setUp(): void
    {
        parent::setUp();
        config(['telegram.bot_token' => self::TOKEN]);
    }

    public function testValidFreshInitDataResolvesBinding(): void
    {
        $user = TestContactableUser::create(['name' => 'A']);
        Bindings::bindLaravelUser($user, 42, 5);
        config(['teleframe.primary_account_id' => 5]);

        $guard = $this->guardWithHeader($this->makeInitData(42, time() - 10));

        self::assertSame($user->id, $guard->id());
        self::assertTrue($guard->check());
        self::assertFalse($guard->guest());
        self::assertTrue($guard->hasUser());
        self::assertTrue($guard->validate());
        self::assertSame($user->id, $guard->user()->getAuthIdentifier());
    }

    public function testExplicitAccountOverrideResolvesOtherTenant(): void
    {
        $user = TestContactableUser::create(['name' => 'A']);
        Bindings::bindLaravelUser($user, 42, 9);
        config(['teleframe.primary_account_id' => 5]);

        $request = Request::create('/', 'GET', ['account_id' => '9']);
        $request->headers->set('X-Telegram-Init-Data', $this->makeInitData(42, time() - 10));

        $guard = new TgWebAppGuard($request);

        self::assertSame($user->id, $guard->id());
    }

    public function testUnboundIdentityRejected(): void
    {
        $guard = $this->guardWithHeader($this->makeInitData(987654321, time() - 10));

        self::assertNull($guard->user());
        self::assertNull($guard->id());
    }

    public function testExpiredInitDataRejected(): void
    {
        $user = TestContactableUser::create(['name' => 'A']);
        Bindings::bindLaravelUser($user, 42, 5);
        config(['teleframe.primary_account_id' => 5]);

        $guard = $this->guardWithHeader($this->makeInitData(42, time() - 3600));

        self::assertNull($guard->user());
    }

    public function testMissingAuthDateRejected(): void
    {
        $user = TestContactableUser::create(['name' => 'A']);
        Bindings::bindLaravelUser($user, 42, 5);
        config(['teleframe.primary_account_id' => 5]);

        $guard = $this->guardWithHeader($this->makeInitDataWithoutAuthDate(42));

        self::assertNull($guard->user());
    }

    public function testForgedHashRejected(): void
    {
        $user = TestContactableUser::create(['name' => 'A']);
        Bindings::bindLaravelUser($user, 42, 5);
        config(['teleframe.primary_account_id' => 5]);

        // Signed with the WRONG token: hash mismatch against the configured one.
        $forged = $this->makeInitDataWithToken(42, time() - 10, 'garbage:token');

        self::assertNull($this->guardWithHeader($forged)->user());
    }

    public function testMissingInitDataRejected(): void
    {
        self::assertNull((new TgWebAppGuard(Request::create('/')))->user());
    }

    public function testMissingBotTokenRejected(): void
    {
        config(['telegram.bot_token' => null]);

        $guard = $this->guardWithHeader($this->makeInitData(42, time() - 10));

        self::assertNull($guard->user());
    }

    public function testSetUserShortCircuitsResolution(): void
    {
        $guard = new TgWebAppGuard(Request::create('/'));
        $user = TestContactableUser::create(['name' => 'A']);
        $guard->setUser($user);

        self::assertSame($user->id, $guard->id());
    }

    public function testConfigurableFreshnessWindow(): void
    {
        config(['teleframe.identity.miniapp_auth_max_age' => 5]);
        $user = TestContactableUser::create(['name' => 'A']);
        Bindings::bindLaravelUser($user, 42, 5);
        config(['teleframe.primary_account_id' => 5]);

        self::assertNull($this->guardWithHeader($this->makeInitData(42, time() - 30))->user());
        self::assertNotNull($this->guardWithHeader($this->makeInitData(42, time() - 2))->user());
    }

    private function guardWithHeader(string $initData): TgWebAppGuard
    {
        return new TgWebAppGuard($this->requestWithHeader($initData));
    }

    private function requestWithHeader(string $initData): Request
    {
        $request = Request::create('/');
        $request->headers->set('X-Telegram-Init-Data', $initData);

        return $request;
    }

    private function makeInitData(int $tgUserId, int $authDate): string
    {
        return $this->makeInitDataWithToken($tgUserId, $authDate, self::TOKEN);
    }

    private function makeInitDataWithToken(int $tgUserId, int $authDate, string $token): string
    {
        $user = json_encode(['id' => $tgUserId, 'first_name' => 'Tester'], JSON_THROW_ON_ERROR);
        $checkString = "auth_date={$authDate}\nuser={$user}";
        $secret = hash_hmac('sha256', $token, 'WebAppData', true);
        $hash = hash_hmac('sha256', $checkString, $secret);

        return 'user=' . rawurlencode($user) . '&auth_date=' . $authDate . '&hash=' . $hash;
    }

    private function makeInitDataWithoutAuthDate(int $tgUserId): string
    {
        $user = json_encode(['id' => $tgUserId, 'first_name' => 'Tester'], JSON_THROW_ON_ERROR);
        $checkString = "user={$user}";
        $secret = hash_hmac('sha256', self::TOKEN, 'WebAppData', true);
        $hash = hash_hmac('sha256', $checkString, $secret);

        return 'user=' . rawurlencode($user) . '&hash=' . $hash;
    }
}