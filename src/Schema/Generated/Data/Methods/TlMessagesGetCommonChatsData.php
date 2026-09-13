<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getCommonChats (crc32 e40ca104), returns messages.Chats. */
final class TlMessagesGetCommonChatsData extends Data
{
    public const METHOD = 'messages.getCommonChats';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $userId,
    public int $maxId,
    public int $limit,
    ) {
    }
}
