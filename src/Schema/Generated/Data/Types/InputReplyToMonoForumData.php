<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputReplyToMonoForum of InputReplyTo.
 */
final class InputReplyToMonoForumData extends TlInputReplyToAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputPeerAbstractData $monoforumPeerId,
    ) {
    }
}
