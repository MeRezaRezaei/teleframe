<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Identity\Support;

/**
 * Contactable notifiable whose route override returns null — the Laravel
 * channel convention meaning "do not deliver" (Q9 explicit null-route skip).
 */
class TestNullRouteUser extends TestContactableUser
{
    public function routeNotificationForTelegram(): ?array
    {
        return null;
    }
}