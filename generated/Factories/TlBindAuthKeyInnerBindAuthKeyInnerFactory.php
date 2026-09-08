<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlBindAuthKeyInnerBindAuthKeyInner (bind_auth_key_inner). */
final class TlBindAuthKeyInnerBindAuthKeyInnerFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBindAuthKeyInnerBindAuthKeyInner> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBindAuthKeyInnerBindAuthKeyInner::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'nonce' => 1001,
            'temp_auth_key_id' => 1002,
            'perm_auth_key_id' => 1003,
            'temp_session_id' => 1004,
            'expires_at' => 5,
        ];
    }
}
