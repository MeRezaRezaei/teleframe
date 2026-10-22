<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for paymentCharge of PaymentCharge.
 */
final class PaymentChargeData extends TlPaymentChargeAbstractData
{
    public function __construct(
    public string $id,
    public string $providerChargeId,
    ) {
    }
}
