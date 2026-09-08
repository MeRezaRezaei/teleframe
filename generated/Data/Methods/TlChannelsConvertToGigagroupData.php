<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.convertToGigagroup (crc32 0b290c69), returns Updates. */
final class TlChannelsConvertToGigagroupData extends Data
{
    public const METHOD = 'channels.convertToGigagroup';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $channel,
    ) {
    }
}
