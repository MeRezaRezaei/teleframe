<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method payments.getStarGifts (crc32 c4563590), returns payments.StarGifts. */
final class TlPaymentsGetStarGiftsData extends Data
{
    public const METHOD = 'payments.getStarGifts';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $hash,
    ) {
    }
}
