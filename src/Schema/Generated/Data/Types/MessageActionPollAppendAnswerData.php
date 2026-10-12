<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messageActionPollAppendAnswer of MessageAction.
 */
final class MessageActionPollAppendAnswerData extends TlMessageActionAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPollAnswerAbstractData $answer,
    ) {
    }
}
