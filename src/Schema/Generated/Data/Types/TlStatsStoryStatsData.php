<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for stats.storyStats of stats.StoryStats.
 */
final class TlStatsStoryStatsData extends TlStatsStoryStatsAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStatsGraphAbstractData $viewsGraph,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStatsGraphAbstractData $reactionsByEmotionGraph,
    ) {
    }
}
