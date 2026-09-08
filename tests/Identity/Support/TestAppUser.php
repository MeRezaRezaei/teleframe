<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Identity\Support;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use MeRezaRezaei\Teleframe\Identity\HasUserTelegram;

/**
 * Minimum Laravel User for the mini-app user-account (Q10 login side):
 * Authenticatable + the login-side trait (which composes HasTelegram).
 */
class TestAppUser extends Model implements AuthenticatableContract
{
    use Authenticatable, HasUserTelegram;

    protected $table = 'users';

    protected $guarded = [];
}