<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputPasskeyResponseInputPasskeyResponseLogin (inputPasskeyResponseLogin). */
final class TlInputPasskeyResponseInputPasskeyResponseLoginFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputPasskeyResponseInputPasskeyResponseLogin> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputPasskeyResponseInputPasskeyResponseLogin::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'client_data' => (string) new \Symfony\Component\Uid\UuidV7(),
            'authenticator_data' => 'Ynl0ZXMtMg==',
            'signature' => 'Ynl0ZXMtMw==',
            'user_handle' => 'user_handle-4',
        ];
    }
}
