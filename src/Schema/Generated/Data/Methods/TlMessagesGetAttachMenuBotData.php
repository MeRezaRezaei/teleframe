<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getAttachMenuBot (crc32 77216192), returns AttachMenuBotsBot. */
final class TlMessagesGetAttachMenuBotData extends Data
{
    public const METHOD = 'messages.getAttachMenuBot';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $bot,
    ) {
    }
}
