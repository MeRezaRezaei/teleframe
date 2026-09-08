<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsBroadcastStatsBroadcastStatsRecent_posts_interactions;

/** Constructor model for stats.broadcastStats of stats.BroadcastStats (crc32 396ca5fc). */
final class TlStatsBroadcastStatsBroadcastStats extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_stats_broadcast_stats_broadcast_stats';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'period' => 'string',
        'followers' => 'string',
        'views_per_post' => 'string',
        'shares_per_post' => 'string',
        'reactions_per_post' => 'string',
        'views_per_story' => 'string',
        'shares_per_story' => 'string',
        'reactions_per_story' => 'string',
        'enabled_notifications' => 'string',
        'growth_graph' => 'string',
        'followers_graph' => 'string',
        'mute_graph' => 'string',
        'top_hours_graph' => 'string',
        'interactions_graph' => 'string',
        'iv_interactions_graph' => 'string',
        'views_by_source_graph' => 'string',
        'new_followers_by_source_graph' => 'string',
        'languages_graph' => 'string',
        'reactions_by_emotion_graph' => 'string',
        'story_interactions_graph' => 'string',
        'story_reactions_by_emotion_graph' => 'string',
    ];

    public function recentPostsInteractions(): HasMany
    {
        return $this->tlChild(TlStatsBroadcastStatsBroadcastStatsRecent_posts_interactions::class);
    }
}
