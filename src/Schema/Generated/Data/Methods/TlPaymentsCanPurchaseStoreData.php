<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method payments.canPurchaseStore (crc32 4fdc5ea7), returns Bool. */
final class TlPaymentsCanPurchaseStoreData extends Data
{
    public const METHOD = 'payments.canPurchaseStore';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $purpose,
    ) {
    }
}
