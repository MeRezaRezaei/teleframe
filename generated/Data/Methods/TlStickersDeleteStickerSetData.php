<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method stickers.deleteStickerSet (crc32 87704394), returns Bool. */
final class TlStickersDeleteStickerSetData extends Data
{
    public const METHOD = 'stickers.deleteStickerSet';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $stickerset,
    ) {
    }
}
