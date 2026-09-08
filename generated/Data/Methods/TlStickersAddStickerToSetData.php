<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method stickers.addStickerToSet (crc32 8653febe), returns messages.StickerSet. */
final class TlStickersAddStickerToSetData extends Data
{
    public const METHOD = 'stickers.addStickerToSet';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $stickerset,
    public mixed $sticker,
    ) {
    }
}
