<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method stories.getChatsToSend (crc32 a56a8b60), returns messages.Chats. */
final class TlStoriesGetChatsToSendData extends Data
{
    public const METHOD = 'stories.getChatsToSend';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    ) {
    }
}
