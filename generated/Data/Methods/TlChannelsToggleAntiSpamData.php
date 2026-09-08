<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.toggleAntiSpam (crc32 68f3e4eb), returns Updates. */
final class TlChannelsToggleAntiSpamData extends Data
{
    public const METHOD = 'channels.toggleAntiSpam';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $channel,
    public mixed $enabled,
    ) {
    }
}
