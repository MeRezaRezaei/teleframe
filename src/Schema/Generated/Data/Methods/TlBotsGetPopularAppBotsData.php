<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method bots.getPopularAppBots (crc32 c2510192), returns bots.PopularAppBots. */
final class TlBotsGetPopularAppBotsData extends Data
{
    public const METHOD = 'bots.getPopularAppBots';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $offset,
    public int $limit,
    ) {
    }
}
