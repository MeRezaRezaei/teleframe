<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsStarsRevenueStatsStarsRevenueStats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsBroadcastStatsBroadcastStats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsMegagroupStatsMegagroupStats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsMessageStatsMessageStats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsPollStatsPollStats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsStoryStatsStoryStats;

/** Anchor model for TL type StatsGraph (spec §4.1). */
final class TlStatsGraph extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_stats_graph_stats_graph';

    protected $guarded = [];

    public function actionsGraph(): HasMany
    {
        return $this->hasMany(TlStatsMegagroupStatsMegagroupStats::class, 'actions_graph');
    }
    public function followersGraph(): HasMany
    {
        return $this->hasMany(TlStatsBroadcastStatsBroadcastStats::class, 'followers_graph');
    }
    public function growthGraph(): HasMany
    {
        return $this->hasMany(TlStatsBroadcastStatsBroadcastStats::class, 'growth_graph');
    }
    public function growthGraphStatsMegagroupStats(): HasMany
    {
        return $this->hasMany(TlStatsMegagroupStatsMegagroupStats::class, 'growth_graph');
    }
    public function interactionsGraph(): HasMany
    {
        return $this->hasMany(TlStatsBroadcastStatsBroadcastStats::class, 'interactions_graph');
    }
    public function ivInteractionsGraph(): HasMany
    {
        return $this->hasMany(TlStatsBroadcastStatsBroadcastStats::class, 'iv_interactions_graph');
    }
    public function languagesGraph(): HasMany
    {
        return $this->hasMany(TlStatsBroadcastStatsBroadcastStats::class, 'languages_graph');
    }
    public function languagesGraphStatsMegagroupStats(): HasMany
    {
        return $this->hasMany(TlStatsMegagroupStatsMegagroupStats::class, 'languages_graph');
    }
    public function membersGraph(): HasMany
    {
        return $this->hasMany(TlStatsMegagroupStatsMegagroupStats::class, 'members_graph');
    }
    public function messagesGraph(): HasMany
    {
        return $this->hasMany(TlStatsMegagroupStatsMegagroupStats::class, 'messages_graph');
    }
    public function muteGraph(): HasMany
    {
        return $this->hasMany(TlStatsBroadcastStatsBroadcastStats::class, 'mute_graph');
    }
    public function newFollowersBySourceGraph(): HasMany
    {
        return $this->hasMany(TlStatsBroadcastStatsBroadcastStats::class, 'new_followers_by_source_graph');
    }
    public function newMembersBySourceGraph(): HasMany
    {
        return $this->hasMany(TlStatsMegagroupStatsMegagroupStats::class, 'new_members_by_source_graph');
    }
    public function reactionsByEmotionGraph(): HasMany
    {
        return $this->hasMany(TlStatsBroadcastStatsBroadcastStats::class, 'reactions_by_emotion_graph');
    }
    public function reactionsByEmotionGraphStatsMessageStats(): HasMany
    {
        return $this->hasMany(TlStatsMessageStatsMessageStats::class, 'reactions_by_emotion_graph');
    }
    public function reactionsByEmotionGraphStatsStoryStats(): HasMany
    {
        return $this->hasMany(TlStatsStoryStatsStoryStats::class, 'reactions_by_emotion_graph');
    }
    public function revenueGraph(): HasMany
    {
        return $this->hasMany(TlPaymentsStarsRevenueStatsStarsRevenueStats::class, 'revenue_graph');
    }
    public function storyInteractionsGraph(): HasMany
    {
        return $this->hasMany(TlStatsBroadcastStatsBroadcastStats::class, 'story_interactions_graph');
    }
    public function storyReactionsByEmotionGraph(): HasMany
    {
        return $this->hasMany(TlStatsBroadcastStatsBroadcastStats::class, 'story_reactions_by_emotion_graph');
    }
    public function topHoursGraph(): HasMany
    {
        return $this->hasMany(TlStatsBroadcastStatsBroadcastStats::class, 'top_hours_graph');
    }
    public function topHoursGraphPaymentsStarsRevenueStats(): HasMany
    {
        return $this->hasMany(TlPaymentsStarsRevenueStatsStarsRevenueStats::class, 'top_hours_graph');
    }
    public function topHoursGraphStatsMegagroupStats(): HasMany
    {
        return $this->hasMany(TlStatsMegagroupStatsMegagroupStats::class, 'top_hours_graph');
    }
    public function viewsBySourceGraph(): HasMany
    {
        return $this->hasMany(TlStatsBroadcastStatsBroadcastStats::class, 'views_by_source_graph');
    }
    public function viewsGraph(): HasMany
    {
        return $this->hasMany(TlStatsMessageStatsMessageStats::class, 'views_graph');
    }
    public function viewsGraphStatsStoryStats(): HasMany
    {
        return $this->hasMany(TlStatsStoryStatsStoryStats::class, 'views_graph');
    }
    public function votesGraph(): HasMany
    {
        return $this->hasMany(TlStatsPollStatsPollStats::class, 'votes_graph');
    }
    public function weekdaysGraph(): HasMany
    {
        return $this->hasMany(TlStatsMegagroupStatsMegagroupStats::class, 'weekdays_graph');
    }
}
