<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method payments.getPaymentForm (crc32 37148dbb), returns payments.PaymentForm. */
final class TlPaymentsGetPaymentFormData extends Data
{
    public const METHOD = 'payments.getPaymentForm';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public mixed $invoice,
    public mixed $themeParams,
    ) {
    }
}
