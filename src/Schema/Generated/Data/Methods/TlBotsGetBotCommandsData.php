<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method bots.getBotCommands (crc32 e34c0dd6), returns Vector<BotCommand>. */
final class TlBotsGetBotCommandsData extends Data
{
    public const METHOD = 'bots.getBotCommands';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $scope,
    public string $langCode,
    ) {
    }
}
