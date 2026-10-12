<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Identity;

use MeRezaRezaei\Teleframe\Identity\Bindings;
use MeRezaRezaei\Teleframe\Tests\Identity\Support\TestContactableUser;

/**
 * HasTelegram (Q9 contactable side): the binding relation, the Laravel-native
 * routeNotificationForTelegram override point, the static User::findTF and
 * the telegramBinding convenience.
 */
class HasTelegramTest extends TestCase
{
    public function testTelegramBindingsRelation(): void
    {
        $user = TestContactableUser::create(['name' => 'A']);
        Bindings::bindLaravelUser($user, 42, 5);

        self::assertSame(1, $user->telegramBindings()->count());
        self::assertSame(42, (int) $user->telegramBindings()->first()->tl_user_id);
    }

    public function testRouteNotificationFromBinding(): void
    {
        $user = TestContactableUser::create(['name' => 'A']);
        Bindings::bindLaravelUser($user, 42, 5);

        self::assertSame(['tg_id' => 42, 'account' => 5], $user->routeNotificationForTelegram());
    }

    public function testRouteNotificationPrefersFirstKnownAccount(): void
    {
        $user = TestContactableUser::create(['name' => 'A']);
        Bindings::bindLaravelUser($user, 42, 9);
        Bindings::bindLaravelUser($user, 43, 3);

        self::assertSame(3, $user->routeNotificationForTelegram()['account']);
    }

    public function testRouteNotificationNullWithoutBinding(): void
    {
        $user = TestContactableUser::create(['name' => 'A']);

        self::assertNull($user->routeNotificationForTelegram());
    }

    public function testFindTF(): void
    {
        $user = TestContactableUser::create(['name' => 'A']);
        Bindings::bindLaravelUser($user, 42, 5);
        config(['teleframe.primary_account_id' => 5]);

        self::assertSame($user->id, TestContactableUser::findTF(42)?->getKey());
        self::assertSame($user->id, TestContactableUser::findTF(42, 5)?->getKey());
        self::assertNull(TestContactableUser::findTF(12345));
    }

    public function testFindTFExplicitAccountIsScoped(): void
    {
        $user = TestContactableUser::create(['name' => 'A']);
        Bindings::bindLaravelUser($user, 42, 5);

        // First-known-account default resolves the sole tenant row.
        self::assertSame($user->id, TestContactableUser::findTF(42)?->getKey());
        // An explicit account the identity never lived on is a clean miss.
        self::assertNull(TestContactableUser::findTF(42, 3));
    }

    public function testTelegramBindingPrimaryAndOverride(): void
    {
        $user = TestContactableUser::create(['name' => 'A']);
        Bindings::bindLaravelUser($user, 42, 5);
        Bindings::bindLaravelUser($user, 42, 7);
        config(['teleframe.primary_account_id' => 7]);

        self::assertSame(7, $user->telegramBinding()?->account_id);
        self::assertSame(5, $user->telegramBinding(5)?->account_id);
    }

    public function testTelegramBindingNullWithoutAny(): void
    {
        $user = TestContactableUser::create(['name' => 'A']);

        self::assertNull($user->telegramBinding());
    }
}