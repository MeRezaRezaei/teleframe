<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for payments.paymentFormStarGift of payments.PaymentForm.
 */
final class TlPaymentsPaymentFormStarGiftData extends TlPaymentsPaymentFormAbstractData
{
    public function __construct(
    public int $formId,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInvoiceAbstractData $invoice,
    ) {
    }
}
