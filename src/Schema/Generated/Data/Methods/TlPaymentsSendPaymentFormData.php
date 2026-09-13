<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method payments.sendPaymentForm (crc32 2d03522f), returns payments.PaymentResult. */
final class TlPaymentsSendPaymentFormData extends Data
{
    public const METHOD = 'payments.sendPaymentForm';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public int $formId,
    public mixed $invoice,
    public ?string $requestedInfoId,
    public ?string $shippingOptionId,
    public mixed $credentials,
    public ?int $tipAmount,
    ) {
    }
}
