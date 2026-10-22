<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\MiniApp;

use Illuminate\Http\Request;
use MeRezaRezaei\Teleframe\Identity\Bindings;
use MeRezaRezaei\Teleframe\Identity\Guards\TgSessionGuard;
use MeRezaRezaei\Teleframe\Identity\Guards\TgWebAppGuard;
use MeRezaRezaei\Teleframe\Tests\Identity\Support\TestContactableUser;

/**
 * Phase 5g roadmap gate — "example Vue mini app runs inside Telegram,
 * authenticated as the bound Laravel user". The guard->binding->User path is
 * proven INTEGRATED here: a request carrying init-data HMAC-signed with a
 * known bot token that names a bound telegram id authenticates as that
 * Laravel User via the `tg-webapp` guard; a forged (wrong-token) and a
 * replayed (stale auth_date) request are rejected as guests. The session-guard
 * variant resolves the same User from a session identity.
 *
 * HMAC reuse: verification is never rewritten here — the router-proof is the
 * SAME `VerifyMiniAppInitData` core in `TgWebAppGuard` (Phase 5b); these
 * tests only construct genuine init-data to feed it.
 */
class MiniAppGuardIntegrationTest extends TestCase
{
    private const TOKEN = '123456:TESTBOTTOKEN';

    protected function setUp(): void
    {
        parent::setUp();
        config(['telegram.bot_token' => self::TOKEN]);
    }

    public function testValidInitDataAuthenticatesBoundLaravelUser(): void
    {
        $user = TestContactableUser::create(['name' => 'A']);
        Bindings::bindLaravelUser($user, 42, 5);
        config(['teleframe.primary_account_id' => 5]);

        $guard = $this->guardWithHeader($this->makeInitData(42, time() - 10));

        self::assertTrue($guard->check());
        self::assertSame($user->id, $guard->id());
        self::assertSame($user->id, $guard->user()->getAuthIdentifier());
        self::assertSame($user->id, $guard->user()->id);
    }

    public function testForgedInitDataIsRejectedAsGuest(): void
    {
        $user = TestContactableUser::create(['name' => 'A']);
        Bindings::bindLaravelUser($user, 42, 5);
        config(['teleframe.primary_account_id' => 5]);

        // Signed with a DIFFERENT token than the configured one.
        $forged = $this->makeInitDataWithToken(42, time() - 10, 'garbage:token');

        $guard = $this->guardWithHeader($forged);

        self::assertFalse($guard->check());
        self::assertTrue($guard->guest());
        self::assertNull($guard->user());
        self::assertNull($guard->id());
    }

    public function testReplayedStaleInitDataIsRejected(): void
    {
        $user = TestContactableUser::create(['name' => 'A']);
        Bindings::bindLaravelUser($user, 42, 5);
        config(['teleframe.primary_account_id' => 5]);
        config(['teleframe.identity.miniapp_auth_max_age' => 5]);

        // Valid HMAC, but auth_date far outside the 5s window => replay window
        // closed by the freshness gate (F4), same as Phase 5b.
        $replayed = $this->makeInitData(42, time() - 3600);

        $guard = $this->guardWithHeader($replayed);

        self::assertNull($guard->user());
        self::assertFalse($guard->check());
    }

    public function testSessionGuardVariantResolvesBoundUser(): void
    {
        $user = TestContactableUser::create(['name' => 'A']);
        Bindings::bindLaravelUser($user, 42, 5);
        config(['teleframe.primary_account_id' => 5]);

        $request = Request::create('/');
        $request->attributes->set('telegram_session_user_id', '42');

        $guard = new TgSessionGuard($request);

        self::assertTrue($guard->check());
        self::assertSame($user->id, $guard->id());
    }

    private function guardWithHeader(string $initData): TgWebAppGuard
    {
        $request = Request::create('/');
        $request->headers->set('X-Telegram-Init-Data', $initData);

        return new TgWebAppGuard($request);
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
}