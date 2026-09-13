<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method stickers.changeSticker (crc32 f5537ebc), returns messages.StickerSet. */
final class TlStickersChangeStickerData extends Data
{
    public const METHOD = 'stickers.changeSticker';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public mixed $sticker,
    public ?string $emoji,
    public mixed $maskCoords,
    public ?string $keywords,
    ) {
    }
}
