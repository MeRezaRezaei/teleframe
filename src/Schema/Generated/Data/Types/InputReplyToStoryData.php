<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputReplyToStory of InputReplyTo.
 */
final class InputReplyToStoryData extends TlInputReplyToAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputPeerAbstractData $peer,
    public int $storyId,
    ) {
    }
}
