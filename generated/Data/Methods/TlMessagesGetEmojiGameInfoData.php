<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getEmojiGameInfo (crc32 fb7e8ca7), returns messages.EmojiGameInfo. */
final class TlMessagesGetEmojiGameInfoData extends Data
{
    public const METHOD = 'messages.getEmojiGameInfo';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    ) {
    }
}
