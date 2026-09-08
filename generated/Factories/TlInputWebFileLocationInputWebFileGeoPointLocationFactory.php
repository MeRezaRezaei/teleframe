<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputWebFileLocationInputWebFileGeoPointLocation (inputWebFileGeoPointLocation). */
final class TlInputWebFileLocationInputWebFileGeoPointLocationFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputWebFileLocationInputWebFileGeoPointLocation> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputWebFileLocationInputWebFileGeoPointLocation::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'geo_point' => (string) new \Symfony\Component\Uid\UuidV7(),
            'access_hash' => 1002,
            'w' => 3,
            'h' => 4,
            'zoom' => 5,
            'scale' => 6,
        ];
    }
}
