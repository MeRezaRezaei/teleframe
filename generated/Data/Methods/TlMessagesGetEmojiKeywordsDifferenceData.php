<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getEmojiKeywordsDifference (crc32 1508b6af), returns EmojiKeywordsDifference. */
final class TlMessagesGetEmojiKeywordsDifferenceData extends Data
{
    public const METHOD = 'messages.getEmojiKeywordsDifference';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $langCode,
    public int $fromVersion,
    ) {
    }
}
