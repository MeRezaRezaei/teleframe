<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessageActionMessageActionGeoProximityReached (messageActionGeoProximityReached). */
final class TlMessageActionMessageActionGeoProximityReachedFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionGeoProximityReached> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionGeoProximityReached::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'from_id' => 1001,
            'to_id' => 1002,
            'distance' => 3,
        ];
    }
}
