<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method payments.assignAppStoreTransaction (crc32 80ed747d), returns Updates. */
final class TlPaymentsAssignAppStoreTransactionData extends Data
{
    public const METHOD = 'payments.assignAppStoreTransaction';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $receipt,
    public mixed $purpose,
    ) {
    }
}
