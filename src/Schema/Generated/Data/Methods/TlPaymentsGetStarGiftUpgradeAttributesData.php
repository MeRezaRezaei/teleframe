<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method payments.getStarGiftUpgradeAttributes (crc32 6d038b58), returns payments.StarGiftUpgradeAttributes. */
final class TlPaymentsGetStarGiftUpgradeAttributesData extends Data
{
    public const METHOD = 'payments.getStarGiftUpgradeAttributes';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $giftId,
    ) {
    }
}
