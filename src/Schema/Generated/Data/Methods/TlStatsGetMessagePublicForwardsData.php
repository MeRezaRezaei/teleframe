<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method stats.getMessagePublicForwards (crc32 5f150144), returns stats.PublicForwards. */
final class TlStatsGetMessagePublicForwardsData extends Data
{
    public const METHOD = 'stats.getMessagePublicForwards';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $channel,
    public int $msgId,
    public string $offset,
    public int $limit,
    ) {
    }
}
