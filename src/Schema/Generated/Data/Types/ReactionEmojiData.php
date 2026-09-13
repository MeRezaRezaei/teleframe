<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for reactionEmoji of Reaction.
 */
final class ReactionEmojiData extends TlReactionAbstractData
{
    public function __construct(
    public string $emoticon,
    ) {
    }
}
