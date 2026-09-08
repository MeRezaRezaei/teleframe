<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputMediaPoll of InputMedia.
 */
final class InputMediaPollData extends TlInputMediaAbstractData
{
    public function __construct(
    public int $flags,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPollAbstractData $poll,
    public ?array $correctAnswers,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputMediaAbstractData $attachedMedia,
    public ?string $solution,
    public ?array $solutionEntities,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputMediaAbstractData $solutionMedia,
    ) {
    }
}
