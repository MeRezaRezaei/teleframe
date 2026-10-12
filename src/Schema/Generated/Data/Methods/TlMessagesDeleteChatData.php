<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.deleteChat (crc32 5bd0ee50), returns Bool. */
final class TlMessagesDeleteChatData extends Data
{
    public const METHOD = 'messages.deleteChat';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $chatId,
    ) {
    }
}
