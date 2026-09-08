<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Identity\Support;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use MeRezaRezaei\Teleframe\Identity\HasTelegram;

/**
 * Minimum Laravel User that is contactable ON Telegram (Q9 side) and a valid
 * Guard target (Authenticatable + Model persistence on `users`).
 */
class TestContactableUser extends Model implements AuthenticatableContract
{
    use Authenticatable, HasTelegram, Notifiable;

    protected $table = 'users';

    protected $guarded = [];
}