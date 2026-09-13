<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getEmojiProfilePhotoGroups (crc32 21a548f3), returns messages.EmojiGroups. */
final class TlMessagesGetEmojiProfilePhotoGroupsData extends Data
{
    public const METHOD = 'messages.getEmojiProfilePhotoGroups';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $hash,
    ) {
    }
}
