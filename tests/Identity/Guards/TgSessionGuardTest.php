<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Identity\Guards;

use Illuminate\Http\Request;
use MeRezaRezaei\Teleframe\Identity\Bindings;
use MeRezaRezaei\Teleframe\Identity\Guards\TgSessionGuard;
use MeRezaRezaei\Teleframe\Tests\Identity\Support\TestContactableUser;
use MeRezaRezaei\Teleframe\Tests\Identity\TestCase;

/**
 * Q10 `tg-session` guard: session/attribute/callable telegram identity ->
 * binding (Q7 primary default) -> bound Laravel User, else null (guest).
 * Source precedence (ruled): callable > request attribute > session keys
 * (telegram_session_user_id, then telegram_user_id legacy).
 */
class TgSessionGuardTest extends TestCase
{
    public function testResolvesFromCallableResolver(): void
    {
        $user = TestContactableUser::create(['name' => 'A']);
        Bindings::bindLaravelUser($user, 42, 5);
        config(['teleframe.primary_account_id' => 5]);

        $guard = new TgSessionGuard(Request::create('/'), static fn (): int => 42);

        self::assertSame($user->id, $guard->id());
        self::assertTrue($guard->check());
        self::assertFalse($guard->guest());
        self::assertTrue($guard->hasUser());
        self::assertTrue($guard->validate());
    }

    public function testResolvesFromRequestAttribute(): void
    {
        $user = TestContactableUser::create(['name' => 'A']);
        Bindings::bindLaravelUser($user, 42, 5);
        config(['teleframe.primary_account_id' => 5]);

        $request = Request::create('/');
        $request->attributes->set('telegram_session_user_id', '42');

        $guard = new TgSessionGuard($request);

        self::assertSame($user->id, $guard->id());
    }

    public function testResolvesFromSessionKey(): void
    {
        $user = TestContactableUser::create(['name' => 'A']);
        Bindings::bindLaravelUser($user, 42, 5);
        config(['teleframe.primary_account_id' => 5]);

        $store = $this->app['session']->driver('array');
        $store->put('telegram_session_user_id', '42');

        $request = Request::create('/');
        $request->setLaravelSession($store);

        $guard = new TgSessionGuard($request);

        self::assertSame($user->id, $guard->id());
    }

    public function testResolvesFromLegacySessionKey(): void
    {
        $user = TestContactableUser::create(['name' => 'A']);
        Bindings::bindLaravelUser($user, 42, 5);
        config(['teleframe.primary_account_id' => 5]);

        $store = $this->app['session']->driver('array');
        $store->put('telegram_user_id', '42');

        $request = Request::create('/');
        $request->setLaravelSession($store);

        $guard = new TgSessionGuard($request);

        self::assertSame($user->id, $guard->id());
    }

    public function testResolvesSessionAccountScoping(): void
    {
        $user = TestContactableUser::create(['name' => 'A']);
        Bindings::bindLaravelUser($user, 42, 9);

        $store = $this->app['session']->driver('array');
        $store->put('telegram_session_user_id', '42');
        $store->put('telegram_session_account_id', '9');

        $request = Request::create('/');
        $request->setLaravelSession($store);

        $guard = new TgSessionGuard($request);

        self::assertSame($user->id, $guard->id());
    }

    public function testAttributeTakesPrecedenceOverSession(): void
    {
        $user = TestContactableUser::create(['name' => 'A']);
        Bindings::bindLaravelUser($user, 42, 5);
        config(['teleframe.primary_account_id' => 5]);

        $store = $this->app['session']->driver('array');
        $store->put('telegram_session_user_id', '999'); // unbound

        $request = Request::create('/');
        $request->setLaravelSession($store);
        $request->attributes->set('telegram_session_user_id', '42'); // bound

        $guard = new TgSessionGuard($request);

        self::assertSame($user->id, $guard->id());
    }

    public function testNoSourceReturnsNull(): void
    {
        $guard = new TgSessionGuard(Request::create('/'));

        self::assertNull($guard->user());
        self::assertNull($guard->id());
        self::assertFalse($guard->check());
        self::assertTrue($guard->guest());
    }

    public function testUnboundIdentityReturnsNull(): void
    {
        $request = Request::create('/');
        $request->attributes->set('telegram_session_user_id', '987654321');

        self::assertNull((new TgSessionGuard($request))->user());
    }

    public function testSetUserShortCircuitsResolution(): void
    {
        $guard = new TgSessionGuard(Request::create('/'));
        $user = TestContactableUser::create(['name' => 'A']);
        $guard->setUser($user);

        self::assertSame($user->id, $guard->id());
    }
}