<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlSecureSecretSettingsSecureSecretSettings (secureSecretSettings). */
final class TlSecureSecretSettingsSecureSecretSettingsFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSecureSecretSettingsSecureSecretSettings> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSecureSecretSettingsSecureSecretSettings::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'secure_algo' => (string) new \Symfony\Component\Uid\UuidV7(),
            'secure_secret' => 'Ynl0ZXMtMg==',
            'secure_secret_id' => 1003,
        ];
    }
}
