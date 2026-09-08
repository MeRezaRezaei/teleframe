<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method payments.getStarGiftAuctionState (crc32 5c9ff4d6), returns payments.StarGiftAuctionState. */
final class TlPaymentsGetStarGiftAuctionStateData extends Data
{
    public const METHOD = 'payments.getStarGiftAuctionState';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $auction,
    public int $version,
    ) {
    }
}
