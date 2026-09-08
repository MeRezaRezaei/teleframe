<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.deleteParticipantHistory (crc32 367544db), returns messages.AffectedHistory. */
final class TlChannelsDeleteParticipantHistoryData extends Data
{
    public const METHOD = 'channels.deleteParticipantHistory';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $channel,
    public mixed $participant,
    ) {
    }
}
