<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlRSAPublicKeyRsaPublicKey (rsa_public_key). */
final class TlRSAPublicKeyRsaPublicKeyFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRSAPublicKeyRsaPublicKey> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRSAPublicKeyRsaPublicKey::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'n' => 'n-1',
            'e' => 'e-2',
        ];
    }
}
