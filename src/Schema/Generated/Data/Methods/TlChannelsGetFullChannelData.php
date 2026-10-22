<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.getFullChannel (crc32 08736a09), returns messages.ChatFull. */
final class TlChannelsGetFullChannelData extends Data
{
    public const METHOD = 'channels.getFullChannel';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $channel,
    ) {
    }
}
