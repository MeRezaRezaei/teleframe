<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.setEmojiStickers (crc32 3cd930b7), returns Bool. */
final class TlChannelsSetEmojiStickersData extends Data
{
    public const METHOD = 'channels.setEmojiStickers';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $channel,
    public mixed $stickerset,
    ) {
    }
}
