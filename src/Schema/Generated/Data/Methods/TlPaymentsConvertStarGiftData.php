<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method payments.convertStarGift (crc32 74bf076b), returns Bool. */
final class TlPaymentsConvertStarGiftData extends Data
{
    public const METHOD = 'payments.convertStarGift';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $stargift,
    ) {
    }
}
