<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlStatsMessageStatsMessageStats (stats.messageStats). */
final class TlStatsMessageStatsMessageStatsFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsMessageStatsMessageStats> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsMessageStatsMessageStats::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'views_graph' => 1001,
            'reactions_by_emotion_graph' => 1002,
        ];
    }
}
