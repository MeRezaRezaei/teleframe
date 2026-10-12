<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.getDefaultGroupPhotoEmojis (crc32 915860ae), returns EmojiList. */
final class TlAccountGetDefaultGroupPhotoEmojisData extends Data
{
    public const METHOD = 'account.getDefaultGroupPhotoEmojis';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $hash,
    ) {
    }
}
