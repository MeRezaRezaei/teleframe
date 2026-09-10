<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlStatsGraphStatsGraph (statsGraph). */
final class TlStatsGraphStatsGraphFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsGraphStatsGraph> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsGraphStatsGraph::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'json' => 1002,
            'zoom_token' => 'zoom_token-3',
        ];
    }
}
