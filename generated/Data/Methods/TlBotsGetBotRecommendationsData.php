<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method bots.getBotRecommendations (crc32 a1b70815), returns users.Users. */
final class TlBotsGetBotRecommendationsData extends Data
{
    public const METHOD = 'bots.getBotRecommendations';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $bot,
    ) {
    }
}
