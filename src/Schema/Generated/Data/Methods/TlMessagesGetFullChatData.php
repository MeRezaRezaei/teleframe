<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getFullChat (crc32 aeb00b34), returns messages.ChatFull. */
final class TlMessagesGetFullChatData extends Data
{
    public const METHOD = 'messages.getFullChat';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $chatId,
    ) {
    }
}
