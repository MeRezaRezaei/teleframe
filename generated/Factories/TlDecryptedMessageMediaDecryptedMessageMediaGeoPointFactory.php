<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlDecryptedMessageMediaDecryptedMessageMediaGeoPoint (decryptedMessageMediaGeoPoint). */
final class TlDecryptedMessageMediaDecryptedMessageMediaGeoPointFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDecryptedMessageMediaDecryptedMessageMediaGeoPoint> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDecryptedMessageMediaDecryptedMessageMediaGeoPoint::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'lat' => 0.1,
            'tl_long' => 0.2,
        ];
    }
}
