<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Identity\Support;

/**
 * Notifiable WITHOUT the HasTelegram trait but defining the Laravel-native
 * route override that returns a plain integer telegram id (Q9 integer route).
 */
class TestIntRouteUser extends TestPlainUser
{
    public function routeNotificationForTelegram(): int
    {
        return 555;
    }
}