<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getAttachedStickers (crc32 cc5b67cc), returns Vector<StickerSetCovered>. */
final class TlMessagesGetAttachedStickersData extends Data
{
    public const METHOD = 'messages.getAttachedStickers';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $media,
    ) {
    }
}
