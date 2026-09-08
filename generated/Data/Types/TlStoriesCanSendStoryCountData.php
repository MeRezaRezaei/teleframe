<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for stories.canSendStoryCount of stories.CanSendStoryCount.
 */
final class TlStoriesCanSendStoryCountData extends TlStoriesCanSendStoryCountAbstractData
{
    public function __construct(
    public int $countRemains,
    ) {
    }
}
