<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlStatsMegagroupStatsMegagroupStats (stats.megagroupStats). */
final class TlStatsMegagroupStatsMegagroupStatsFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsMegagroupStatsMegagroupStats> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsMegagroupStatsMegagroupStats::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'period' => 1001,
            'members' => 1002,
            'messages' => 1003,
            'viewers' => 1004,
            'posters' => 1005,
            'growth_graph' => 1006,
            'members_graph' => 1007,
            'new_members_by_source_graph' => 1008,
            'languages_graph' => 1009,
            'messages_graph' => 1010,
            'actions_graph' => 1011,
            'top_hours_graph' => 1012,
            'weekdays_graph' => 1013,
        ];
    }
}
