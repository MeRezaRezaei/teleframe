<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for storyReactionPublicRepost of StoryReaction.
 */
final class StoryReactionPublicRepostData extends TlStoryReactionAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $peerId,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStoryItemAbstractData $story,
    ) {
    }
}
