<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method payments.updateStarGiftPrice (crc32 edbe6ccb), returns Updates. */
final class TlPaymentsUpdateStarGiftPriceData extends Data
{
    public const METHOD = 'payments.updateStarGiftPrice';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $stargift,
    public mixed $resellAmount,
    ) {
    }
}
