<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getEmojiKeywordsLanguages (crc32 4e9963b2), returns Vector<EmojiLanguage>. */
final class TlMessagesGetEmojiKeywordsLanguagesData extends Data
{
    public const METHOD = 'messages.getEmojiKeywordsLanguages';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public array $langCodes,
    ) {
    }
}
