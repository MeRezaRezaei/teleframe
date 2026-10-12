<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messageMediaPoll of MessageMedia.
 */
final class MessageMediaPollData extends TlMessageMediaAbstractData
{
    public function __construct(
    public int $flags,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPollAbstractData $poll,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPollResultsAbstractData $results,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlMessageMediaAbstractData $attachedMedia,
    ) {
    }
}
