<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.togglePreHistoryHidden (crc32 eabbb94c), returns Updates. */
final class TlChannelsTogglePreHistoryHiddenData extends Data
{
    public const METHOD = 'channels.togglePreHistoryHidden';

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
