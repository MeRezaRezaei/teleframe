<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputInvoiceStars of InputInvoice.
 */
final class InputInvoiceStarsData extends TlInputInvoiceAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputStorePaymentPurposeAbstractData $purpose,
    ) {
    }
}
