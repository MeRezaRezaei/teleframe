<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.toggleAutotranslation (crc32 167fc0a1), returns Updates. */
final class TlChannelsToggleAutotranslationData extends Data
{
    public const METHOD = 'channels.toggleAutotranslation';

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
