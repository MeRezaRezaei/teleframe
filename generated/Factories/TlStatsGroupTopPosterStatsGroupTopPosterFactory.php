<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlStatsGroupTopPosterStatsGroupTopPoster (statsGroupTopPoster). */
final class TlStatsGroupTopPosterStatsGroupTopPosterFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsGroupTopPosterStatsGroupTopPoster> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsGroupTopPosterStatsGroupTopPoster::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => 1001,
            'messages' => 2,
            'avg_chars' => 3,
        ];
    }
}
