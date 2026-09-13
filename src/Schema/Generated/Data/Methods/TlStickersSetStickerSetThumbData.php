<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method stickers.setStickerSetThumb (crc32 a76a5392), returns messages.StickerSet. */
final class TlStickersSetStickerSetThumbData extends Data
{
    public const METHOD = 'stickers.setStickerSetThumb';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public mixed $stickerset,
    public mixed $thumb,
    public ?int $thumbDocumentId,
    ) {
    }
}
