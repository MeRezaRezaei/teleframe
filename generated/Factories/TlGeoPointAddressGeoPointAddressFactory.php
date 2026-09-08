<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlGeoPointAddressGeoPointAddress (geoPointAddress). */
final class TlGeoPointAddressGeoPointAddressFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlGeoPointAddressGeoPointAddress> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlGeoPointAddressGeoPointAddress::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'country_iso2' => 'country_iso2-2',
            'state' => 'state-3',
            'city' => 'city-4',
            'street' => 'street-5',
        ];
    }
}
