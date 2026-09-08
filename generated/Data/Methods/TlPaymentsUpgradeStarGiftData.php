<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method payments.upgradeStarGift (crc32 aed6e4f5), returns Updates. */
final class TlPaymentsUpgradeStarGiftData extends Data
{
    public const METHOD = 'payments.upgradeStarGift';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $keepOriginalDetails,
    public mixed $stargift,
    ) {
    }
}
