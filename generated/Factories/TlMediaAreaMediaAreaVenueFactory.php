<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMediaAreaMediaAreaVenue (mediaAreaVenue). */
final class TlMediaAreaMediaAreaVenueFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMediaAreaMediaAreaVenue> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMediaAreaMediaAreaVenue::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'coordinates' => 1001,
            'geo' => 1002,
            'title' => 'title-3',
            'address' => 'address-4',
            'provider' => 'provider-5',
            'venue_id' => 'venue_id-6',
            'venue_type' => 'venue_type-7',
        ];
    }
}
