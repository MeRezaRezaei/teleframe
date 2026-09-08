<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputGeoPointInputGeoPoint (inputGeoPoint). */
final class TlInputGeoPointInputGeoPointFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputGeoPointInputGeoPoint> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputGeoPointInputGeoPoint::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'lat' => 0.2,
            'tl_long' => 0.3,
            'accuracy_radius' => 4,
        ];
    }
}
