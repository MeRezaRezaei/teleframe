<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.rateTranscribedAudio (crc32 7f1d072f), returns Bool. */
final class TlMessagesRateTranscribedAudioData extends Data
{
    public const METHOD = 'messages.rateTranscribedAudio';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public int $msgId,
    public int $transcriptionId,
    public mixed $good,
    ) {
    }
}
