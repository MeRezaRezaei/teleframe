<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for updateSentStoryReaction of Update.
 */
final class UpdateSentStoryReactionData extends TlUpdateAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $peer,
    public int $storyId,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlReactionAbstractData $reaction,
    ) {
    }
}
