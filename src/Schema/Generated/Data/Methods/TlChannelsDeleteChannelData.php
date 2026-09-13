<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.deleteChannel (crc32 c0111fe3), returns Updates. */
final class TlChannelsDeleteChannelData extends Data
{
    public const METHOD = 'channels.deleteChannel';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $channel,
    ) {
    }
}
