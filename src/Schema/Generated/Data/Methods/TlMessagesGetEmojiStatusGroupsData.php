<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getEmojiStatusGroups (crc32 2ecd56cd), returns messages.EmojiGroups. */
final class TlMessagesGetEmojiStatusGroupsData extends Data
{
    public const METHOD = 'messages.getEmojiStatusGroups';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $hash,
    ) {
    }
}
