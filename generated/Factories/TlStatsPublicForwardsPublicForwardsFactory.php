<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlStatsPublicForwardsPublicForwards (stats.publicForwards). */
final class TlStatsPublicForwardsPublicForwardsFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsPublicForwardsPublicForwards> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsPublicForwardsPublicForwards::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'count' => 2,
            'next_offset' => 'next_offset-3',
        ];
    }
}
