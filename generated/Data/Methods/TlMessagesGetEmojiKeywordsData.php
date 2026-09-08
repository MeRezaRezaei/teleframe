<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getEmojiKeywords (crc32 35a0e062), returns EmojiKeywordsDifference. */
final class TlMessagesGetEmojiKeywordsData extends Data
{
    public const METHOD = 'messages.getEmojiKeywords';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $langCode,
    ) {
    }
}
