<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.toggleSlowMode (crc32 edd49ef0), returns Updates. */
final class TlChannelsToggleSlowModeData extends Data
{
    public const METHOD = 'channels.toggleSlowMode';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $channel,
    public int $seconds,
    ) {
    }
}
