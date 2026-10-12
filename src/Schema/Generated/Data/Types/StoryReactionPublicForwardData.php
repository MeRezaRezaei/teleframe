<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for storyReactionPublicForward of StoryReaction.
 */
final class StoryReactionPublicForwardData extends TlStoryReactionAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlMessageAbstractData $message,
    ) {
    }
}
