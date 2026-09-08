<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlGeoPointGeoPoint (geoPoint). */
final class TlGeoPointGeoPointFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlGeoPointGeoPoint> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlGeoPointGeoPoint::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'tl_long' => 0.2,
            'lat' => 0.3,
            'access_hash' => 1004,
            'accuracy_radius' => 5,
        ];
    }
}
