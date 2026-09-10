<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputMediaInputMediaGeoLive (inputMediaGeoLive). */
final class TlInputMediaInputMediaGeoLiveFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputMediaInputMediaGeoLive> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputMediaInputMediaGeoLive::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'stopped' => true,
            'geo_point' => 1003,
            'heading' => 4,
            'period' => 5,
            'proximity_notification_radius' => 6,
        ];
    }
}
