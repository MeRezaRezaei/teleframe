<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsAbsValueAndPrev;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsBroadcastStatsBroadcastStatsRecent_posts_interactions;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsDateRangeDays;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsGraph;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsPercentValue;

/** Constructor model for stats.broadcastStats of stats.BroadcastStats (crc32 396ca5fc). */
final class TlStatsBroadcastStatsBroadcastStats extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_stats_broadcast_stats_broadcast_stats';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function recentPostsInteractions(): HasMany
    {
        return $this->tlChild(TlStatsBroadcastStatsBroadcastStatsRecent_posts_interactions::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(TlStatsDateRangeDays::class, 'period');
    }
    public function followers(): BelongsTo
    {
        return $this->belongsTo(TlStatsAbsValueAndPrev::class, 'followers');
    }
    public function viewsPerPost(): BelongsTo
    {
        return $this->belongsTo(TlStatsAbsValueAndPrev::class, 'views_per_post');
    }
    public function sharesPerPost(): BelongsTo
    {
        return $this->belongsTo(TlStatsAbsValueAndPrev::class, 'shares_per_post');
    }
    public function reactionsPerPost(): BelongsTo
    {
        return $this->belongsTo(TlStatsAbsValueAndPrev::class, 'reactions_per_post');
    }
    public function viewsPerStory(): BelongsTo
    {
        return $this->belongsTo(TlStatsAbsValueAndPrev::class, 'views_per_story');
    }
    public function sharesPerStory(): BelongsTo
    {
        return $this->belongsTo(TlStatsAbsValueAndPrev::class, 'shares_per_story');
    }
    public function reactionsPerStory(): BelongsTo
    {
        return $this->belongsTo(TlStatsAbsValueAndPrev::class, 'reactions_per_story');
    }
    public function enabledNotifications(): BelongsTo
    {
        return $this->belongsTo(TlStatsPercentValue::class, 'enabled_notifications');
    }
    public function growthGraph(): BelongsTo
    {
        return $this->belongsTo(TlStatsGraph::class, 'growth_graph');
    }
    public function followersGraph(): BelongsTo
    {
        return $this->belongsTo(TlStatsGraph::class, 'followers_graph');
    }
    public function muteGraph(): BelongsTo
    {
        return $this->belongsTo(TlStatsGraph::class, 'mute_graph');
    }
    public function topHoursGraph(): BelongsTo
    {
        return $this->belongsTo(TlStatsGraph::class, 'top_hours_graph');
    }
    public function interactionsGraph(): BelongsTo
    {
        return $this->belongsTo(TlStatsGraph::class, 'interactions_graph');
    }
    public function ivInteractionsGraph(): BelongsTo
    {
        return $this->belongsTo(TlStatsGraph::class, 'iv_interactions_graph');
    }
    public function viewsBySourceGraph(): BelongsTo
    {
        return $this->belongsTo(TlStatsGraph::class, 'views_by_source_graph');
    }
    public function newFollowersBySourceGraph(): BelongsTo
    {
        return $this->belongsTo(TlStatsGraph::class, 'new_followers_by_source_graph');
    }
    public function languagesGraph(): BelongsTo
    {
        return $this->belongsTo(TlStatsGraph::class, 'languages_graph');
    }
    public function reactionsByEmotionGraph(): BelongsTo
    {
        return $this->belongsTo(TlStatsGraph::class, 'reactions_by_emotion_graph');
    }
    public function storyInteractionsGraph(): BelongsTo
    {
        return $this->belongsTo(TlStatsGraph::class, 'story_interactions_graph');
    }
    public function storyReactionsByEmotionGraph(): BelongsTo
    {
        return $this->belongsTo(TlStatsGraph::class, 'story_reactions_by_emotion_graph');
    }
}
