<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.startBot (crc32 e6df7378), returns Updates. */
final class TlMessagesStartBotData extends Data
{
    public const METHOD = 'messages.startBot';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $bot,
    public mixed $peer,
    public int $randomId,
    public string $startParam,
    ) {
    }
}
