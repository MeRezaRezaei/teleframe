<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method payments.assignPlayMarketTransaction (crc32 dffd50d3), returns Updates. */
final class TlPaymentsAssignPlayMarketTransactionData extends Data
{
    public const METHOD = 'payments.assignPlayMarketTransaction';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $receipt,
    public mixed $purpose,
    ) {
    }
}
