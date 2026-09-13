<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method payments.getStarGiftAuctionAcquiredGifts (crc32 6ba2cbec), returns payments.StarGiftAuctionAcquiredGifts. */
final class TlPaymentsGetStarGiftAuctionAcquiredGiftsData extends Data
{
    public const METHOD = 'payments.getStarGiftAuctionAcquiredGifts';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $giftId,
    ) {
    }
}
