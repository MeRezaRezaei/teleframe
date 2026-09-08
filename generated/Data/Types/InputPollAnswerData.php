<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputPollAnswer of PollAnswer.
 */
final class InputPollAnswerData extends TlPollAnswerAbstractData
{
    public function __construct(
    public int $flags,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlTextWithEntitiesAbstractData $text,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputMediaAbstractData $media,
    ) {
    }
}
