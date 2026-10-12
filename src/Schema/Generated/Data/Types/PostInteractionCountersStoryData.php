<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for postInteractionCountersStory of PostInteractionCounters.
 */
final class PostInteractionCountersStoryData extends TlPostInteractionCountersAbstractData
{
    public function __construct(
    public int $storyId,
    public int $views,
    public int $forwards,
    public int $reactions,
    ) {
    }
}
