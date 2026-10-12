<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method stickers.replaceSticker (crc32 4696459a), returns messages.StickerSet. */
final class TlStickersReplaceStickerData extends Data
{
    public const METHOD = 'stickers.replaceSticker';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $sticker,
    public mixed $newSticker,
    ) {
    }
}
