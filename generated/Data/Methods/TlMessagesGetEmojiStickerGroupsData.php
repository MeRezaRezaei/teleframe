<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getEmojiStickerGroups (crc32 1dd840f5), returns messages.EmojiGroups. */
final class TlMessagesGetEmojiStickerGroupsData extends Data
{
    public const METHOD = 'messages.getEmojiStickerGroups';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $hash,
    ) {
    }
}
