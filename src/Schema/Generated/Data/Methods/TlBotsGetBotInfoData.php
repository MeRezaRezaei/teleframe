<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method bots.getBotInfo (crc32 dcd914fd), returns bots.BotInfo. */
final class TlBotsGetBotInfoData extends Data
{
    public const METHOD = 'bots.getBotInfo';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public mixed $bot,
    public string $langCode,
    ) {
    }
}
