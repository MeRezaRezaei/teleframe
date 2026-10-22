<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method stickers.suggestShortName (crc32 4dafc503), returns stickers.SuggestedShortName. */
final class TlStickersSuggestShortNameData extends Data
{
    public const METHOD = 'stickers.suggestShortName';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $title,
    ) {
    }
}
