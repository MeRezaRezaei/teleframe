<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for reactionCount of ReactionCount.
 */
final class ReactionCountData extends TlReactionCountAbstractData
{
    public function __construct(
    public int $flags,
    public ?int $chosenOrder,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlReactionAbstractData $reaction,
    public int $count,
    ) {
    }
}
