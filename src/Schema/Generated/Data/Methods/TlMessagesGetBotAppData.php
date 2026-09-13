<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getBotApp (crc32 34fdc5c3), returns messages.BotApp. */
final class TlMessagesGetBotAppData extends Data
{
    public const METHOD = 'messages.getBotApp';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $app,
    public int $hash,
    ) {
    }
}
