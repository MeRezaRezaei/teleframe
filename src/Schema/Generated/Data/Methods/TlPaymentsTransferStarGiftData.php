<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method payments.transferStarGift (crc32 7f18176a), returns Updates. */
final class TlPaymentsTransferStarGiftData extends Data
{
    public const METHOD = 'payments.transferStarGift';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $stargift,
    public mixed $toId,
    ) {
    }
}
