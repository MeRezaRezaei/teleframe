<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Identity;

use MeRezaRezaei\Teleframe\Tests\Identity\Support\TestAppUser;

/**
 * HasUserTelegram (Q10 login side): bindTelegramUser, the reverse
 * telegramUserId / telegramSessionAccount resolvers, and the composed
 * HasTelegram surface (relations + route + findTF carry over intact).
 */
class HasUserTelegramTest extends TestCase
{
    public function testBindTelegramUserCreatesMorphRow(): void
    {
        $user = TestAppUser::create(['name' => 'App']);
        $binding = $user->bindTelegramUser(42, 4);

        self::assertSame(42, $binding->tl_user_id);
        self::assertSame(4, $binding->account_id);
        self::assertSame($user->id, (int) $binding->fresh()->user_id);
        self::assertSame($user->id, (int) $binding->resolver()->getKey());
    }

    public function testBindTelegramUserIsIdempotent(): void
    {
        $user = TestAppUser::create(['name' => 'App']);
        $user->bindTelegramUser(42, 4);
        $user->bindTelegramUser(42, 4);

        self::assertSame(1, $user->telegramBindings()->count());
    }

    public function testComposesHasTelegramSurface(): void
    {
        $user = TestAppUser::create(['name' => 'App']);
        $user->bindTelegramUser(42, 4);

        self::assertSame(1, $user->telegramBindings()->count());
        self::assertSame(['tg_id' => 42, 'account' => 4], $user->routeNotificationForTelegram());

        config(['teleframe.primary_account_id' => 4]);
        self::assertSame($user->id, TestAppUser::findTF(42)?->getKey());
    }

    public function testTelegramUserIdAndSessionAccount(): void
    {
        $user = TestAppUser::create(['name' => 'App']);
        self::assertNull($user->telegramUserId());
        self::assertNull($user->telegramSessionAccount());

        $user->bindTelegramUser(42, 4);

        self::assertSame(42, $user->telegramUserId());
        self::assertSame(4, $user->telegramSessionAccount());
        self::assertSame(42, $user->telegramUserId(4));
    }

    public function testTelegramUserIdPrimaryDefault(): void
    {
        $user = TestAppUser::create(['name' => 'App']);
        $user->bindTelegramUser(42, 9);
        $user->bindTelegramUser(43, 3);
        config(['teleframe.primary_account_id' => 9]);

        self::assertSame(42, $user->telegramUserId());
        self::assertSame(9, $user->telegramSessionAccount());
    }
}