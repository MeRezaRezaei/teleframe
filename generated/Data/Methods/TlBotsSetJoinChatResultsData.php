<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method bots.setJoinChatResults (crc32 e71a4810), returns Bool. */
final class TlBotsSetJoinChatResultsData extends Data
{
    public const METHOD = 'bots.setJoinChatResults';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $queryId,
    public mixed $result,
    ) {
    }
}
