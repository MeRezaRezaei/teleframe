<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getChats (crc32 49e9528f), returns messages.Chats. */
final class TlMessagesGetChatsData extends Data
{
    public const METHOD = 'messages.getChats';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public array $id,
    ) {
    }
}
