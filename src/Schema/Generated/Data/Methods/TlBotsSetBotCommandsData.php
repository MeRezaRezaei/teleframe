<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method bots.setBotCommands (crc32 0517165a), returns Bool. */
final class TlBotsSetBotCommandsData extends Data
{
    public const METHOD = 'bots.setBotCommands';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $scope,
    public string $langCode,
    public array $commands,
    ) {
    }
}
