<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMediaAreaMediaAreaWeather (mediaAreaWeather). */
final class TlMediaAreaMediaAreaWeatherFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMediaAreaMediaAreaWeather> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMediaAreaMediaAreaWeather::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'coordinates' => (string) new \Symfony\Component\Uid\UuidV7(),
            'emoji' => 'emoji-2',
            'temperature_c' => 0.3,
            'color' => 4,
        ];
    }
}
