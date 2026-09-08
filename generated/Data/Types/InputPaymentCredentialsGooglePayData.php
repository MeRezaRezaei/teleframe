<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputPaymentCredentialsGooglePay of InputPaymentCredentials.
 */
final class InputPaymentCredentialsGooglePayData extends TlInputPaymentCredentialsAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlDataJSONAbstractData $paymentToken,
    ) {
    }
}
