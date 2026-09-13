<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method payments.getBankCardData (crc32 2e79d779), returns payments.BankCardData. */
final class TlPaymentsGetBankCardDataData extends Data
{
    public const METHOD = 'payments.getBankCardData';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $number,
    ) {
    }
}
