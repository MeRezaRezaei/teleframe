<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method stickers.renameStickerSet (crc32 124b1c00), returns messages.StickerSet. */
final class TlStickersRenameStickerSetData extends Data
{
    public const METHOD = 'stickers.renameStickerSet';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $stickerset,
    public string $title,
    ) {
    }
}
