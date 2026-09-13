<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputInvoicePremiumGiftStars of InputInvoice.
 */
final class InputInvoicePremiumGiftStarsData extends TlInputInvoiceAbstractData
{
    public function __construct(
    public int $flags,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputUserAbstractData $userId,
    public int $months,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlTextWithEntitiesAbstractData $message,
    ) {
    }
}
