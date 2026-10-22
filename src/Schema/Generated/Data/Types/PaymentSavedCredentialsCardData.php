<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for paymentSavedCredentialsCard of PaymentSavedCredentials.
 */
final class PaymentSavedCredentialsCardData extends TlPaymentSavedCredentialsAbstractData
{
    public function __construct(
    public string $id,
    public string $title,
    ) {
    }
}
