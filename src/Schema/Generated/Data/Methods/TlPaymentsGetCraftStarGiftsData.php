<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method payments.getCraftStarGifts (crc32 fd05dd00), returns payments.SavedStarGifts. */
final class TlPaymentsGetCraftStarGiftsData extends Data
{
    public const METHOD = 'payments.getCraftStarGifts';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $giftId,
    public string $offset,
    public int $limit,
    ) {
    }
}
