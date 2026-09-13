<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for updateNewStoryReaction of Update.
 */
final class UpdateNewStoryReactionData extends TlUpdateAbstractData
{
    public function __construct(
    public int $storyId,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $peer,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlReactionAbstractData $reaction,
    ) {
    }
}
