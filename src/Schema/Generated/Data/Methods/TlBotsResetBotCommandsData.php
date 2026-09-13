<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method bots.resetBotCommands (crc32 3d8de0f9), returns Bool. */
final class TlBotsResetBotCommandsData extends Data
{
    public const METHOD = 'bots.resetBotCommands';

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
