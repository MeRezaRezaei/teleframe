<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method bots.setBotMenuButton (crc32 4504d54f), returns Bool. */
final class TlBotsSetBotMenuButtonData extends Data
{
    public const METHOD = 'bots.setBotMenuButton';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $userId,
    public mixed $button,
    ) {
    }
}
