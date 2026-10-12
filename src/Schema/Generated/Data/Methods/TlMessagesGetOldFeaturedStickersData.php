<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getOldFeaturedStickers (crc32 7ed094a1), returns messages.FeaturedStickers. */
final class TlMessagesGetOldFeaturedStickersData extends Data
{
    public const METHOD = 'messages.getOldFeaturedStickers';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $offset,
    public int $limit,
    public int $hash,
    ) {
    }
}
