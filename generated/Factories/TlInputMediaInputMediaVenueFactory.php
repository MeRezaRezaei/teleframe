<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputMediaInputMediaVenue (inputMediaVenue). */
final class TlInputMediaInputMediaVenueFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputMediaInputMediaVenue> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputMediaInputMediaVenue::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'geo_point' => 1001,
            'title' => 'title-2',
            'address' => 'address-3',
            'provider' => 'provider-4',
            'venue_id' => 'venue_id-5',
            'venue_type' => 'venue_type-6',
        ];
    }
}
