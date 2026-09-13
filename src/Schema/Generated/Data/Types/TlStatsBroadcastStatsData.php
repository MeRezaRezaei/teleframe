<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for stats.broadcastStats of stats.BroadcastStats.
 */
final class TlStatsBroadcastStatsData extends TlStatsBroadcastStatsAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStatsDateRangeDaysAbstractData $period,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStatsAbsValueAndPrevAbstractData $followers,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStatsAbsValueAndPrevAbstractData $viewsPerPost,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStatsAbsValueAndPrevAbstractData $sharesPerPost,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStatsAbsValueAndPrevAbstractData $reactionsPerPost,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStatsAbsValueAndPrevAbstractData $viewsPerStory,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStatsAbsValueAndPrevAbstractData $sharesPerStory,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStatsAbsValueAndPrevAbstractData $reactionsPerStory,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStatsPercentValueAbstractData $enabledNotifications,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStatsGraphAbstractData $growthGraph,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStatsGraphAbstractData $followersGraph,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStatsGraphAbstractData $muteGraph,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStatsGraphAbstractData $topHoursGraph,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStatsGraphAbstractData $interactionsGraph,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStatsGraphAbstractData $ivInteractionsGraph,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStatsGraphAbstractData $viewsBySourceGraph,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStatsGraphAbstractData $newFollowersBySourceGraph,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStatsGraphAbstractData $languagesGraph,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStatsGraphAbstractData $reactionsByEmotionGraph,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStatsGraphAbstractData $storyInteractionsGraph,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStatsGraphAbstractData $storyReactionsByEmotionGraph,
    public array $recentPostsInteractions,
    ) {
    }
}
