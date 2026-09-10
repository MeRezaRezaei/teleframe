<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlStatsStoryStatsStoryStats (stats.storyStats). */
final class TlStatsStoryStatsStoryStatsFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsStoryStatsStoryStats> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsStoryStatsStoryStats::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'views_graph' => 1001,
            'reactions_by_emotion_graph' => 1002,
        ];
    }
}
