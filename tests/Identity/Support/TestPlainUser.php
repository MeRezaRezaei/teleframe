<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Identity\Support;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;

/**
 * Plain Laravel User with NO routeNotificationForTelegram method — exercises
 * the TelegramChannel's default-sender fallback / binding-lookup branch.
 */
class TestPlainUser extends Model implements AuthenticatableContract
{
    use Authenticatable;

    protected $table = 'users';

    protected $guarded = [];
}