<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getStickers (crc32 d5a5d3a1), returns messages.Stickers. */
final class TlMessagesGetStickersData extends Data
{
    public const METHOD = 'messages.getStickers';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $emoticon,
    public int $hash,
    ) {
    }
}
