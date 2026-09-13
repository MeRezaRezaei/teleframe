<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputInvoicePremiumGiftCode of InputInvoice.
 */
final class InputInvoicePremiumGiftCodeData extends TlInputInvoiceAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputStorePaymentPurposeAbstractData $purpose,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPremiumGiftCodeOptionAbstractData $option,
    ) {
    }
}
