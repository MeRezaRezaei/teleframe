<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlStatsBroadcastStatsBroadcastStats (stats.broadcastStats). */
final class TlStatsBroadcastStatsBroadcastStatsFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsBroadcastStatsBroadcastStats> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsBroadcastStatsBroadcastStats::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'period' => 1001,
            'followers' => 1002,
            'views_per_post' => 1003,
            'shares_per_post' => 1004,
            'reactions_per_post' => 1005,
            'views_per_story' => 1006,
            'shares_per_story' => 1007,
            'reactions_per_story' => 1008,
            'enabled_notifications' => 1009,
            'growth_graph' => 1010,
            'followers_graph' => 1011,
            'mute_graph' => 1012,
            'top_hours_graph' => 1013,
            'interactions_graph' => 1014,
            'iv_interactions_graph' => 1015,
            'views_by_source_graph' => 1016,
            'new_followers_by_source_graph' => 1017,
            'languages_graph' => 1018,
            'reactions_by_emotion_graph' => 1019,
            'story_interactions_graph' => 1020,
            'story_reactions_by_emotion_graph' => 1021,
        ];
    }
}
