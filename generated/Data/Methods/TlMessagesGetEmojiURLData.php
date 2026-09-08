<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getEmojiURL (crc32 d5b10c26), returns EmojiURL. */
final class TlMessagesGetEmojiURLData extends Data
{
    public const METHOD = 'messages.getEmojiURL';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $langCode,
    ) {
    }
}
