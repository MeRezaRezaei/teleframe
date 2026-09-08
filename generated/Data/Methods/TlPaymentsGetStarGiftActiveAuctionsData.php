<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method payments.getStarGiftActiveAuctions (crc32 a5d0514d), returns payments.StarGiftActiveAuctions. */
final class TlPaymentsGetStarGiftActiveAuctionsData extends Data
{
    public const METHOD = 'payments.getStarGiftActiveAuctions';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $hash,
    ) {
    }
}
