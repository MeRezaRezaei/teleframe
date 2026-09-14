<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel\Http\Dashboard\Support;

use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Minimal host user model for dashboard feature tests. Controllers never
 * reference App\Models\User — owner_type is the concrete authenticated
 * class — so any Authenticatable works.
 */
final class TestUser extends Authenticatable
{
    /** @var string */
    protected $table = 'users';

    /** @var list<string> */
    protected $fillable = ['name', 'email', 'password'];
}
