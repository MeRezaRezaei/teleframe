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
            'period' => (string) new \Symfony\Component\Uid\UuidV7(),
            'followers' => (string) new \Symfony\Component\Uid\UuidV7(),
            'views_per_post' => (string) new \Symfony\Component\Uid\UuidV7(),
            'shares_per_post' => (string) new \Symfony\Component\Uid\UuidV7(),
            'reactions_per_post' => (string) new \Symfony\Component\Uid\UuidV7(),
            'views_per_story' => (string) new \Symfony\Component\Uid\UuidV7(),
            'shares_per_story' => (string) new \Symfony\Component\Uid\UuidV7(),
            'reactions_per_story' => (string) new \Symfony\Component\Uid\UuidV7(),
            'enabled_notifications' => (string) new \Symfony\Component\Uid\UuidV7(),
            'growth_graph' => (string) new \Symfony\Component\Uid\UuidV7(),
            'followers_graph' => (string) new \Symfony\Component\Uid\UuidV7(),
            'mute_graph' => (string) new \Symfony\Component\Uid\UuidV7(),
            'top_hours_graph' => (string) new \Symfony\Component\Uid\UuidV7(),
            'interactions_graph' => (string) new \Symfony\Component\Uid\UuidV7(),
            'iv_interactions_graph' => (string) new \Symfony\Component\Uid\UuidV7(),
            'views_by_source_graph' => (string) new \Symfony\Component\Uid\UuidV7(),
            'new_followers_by_source_graph' => (string) new \Symfony\Component\Uid\UuidV7(),
            'languages_graph' => (string) new \Symfony\Component\Uid\UuidV7(),
            'reactions_by_emotion_graph' => (string) new \Symfony\Component\Uid\UuidV7(),
            'story_interactions_graph' => (string) new \Symfony\Component\Uid\UuidV7(),
            'story_reactions_by_emotion_graph' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
