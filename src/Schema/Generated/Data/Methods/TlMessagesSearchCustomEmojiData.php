<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.searchCustomEmoji (crc32 2c11c0d7), returns EmojiList. */
final class TlMessagesSearchCustomEmojiData extends Data
{
    public const METHOD = 'messages.searchCustomEmoji';

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
