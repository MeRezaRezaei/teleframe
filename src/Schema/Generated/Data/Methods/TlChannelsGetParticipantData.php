<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.getParticipant (crc32 a0ab6cc6), returns channels.ChannelParticipant. */
final class TlChannelsGetParticipantData extends Data
{
    public const METHOD = 'channels.getParticipant';

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
