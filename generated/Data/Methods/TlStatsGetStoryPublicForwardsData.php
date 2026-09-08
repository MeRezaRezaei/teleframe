<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method stats.getStoryPublicForwards (crc32 a6437ef6), returns stats.PublicForwards. */
final class TlStatsGetStoryPublicForwardsData extends Data
{
    public const METHOD = 'stats.getStoryPublicForwards';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public int $id,
    public string $offset,
    public int $limit,
    ) {
    }
}
