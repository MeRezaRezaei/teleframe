<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputInvoiceBusinessBotTransferStars of InputInvoice.
 */
final class InputInvoiceBusinessBotTransferStarsData extends TlInputInvoiceAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputUserAbstractData $bot,
    public int $stars,
    ) {
    }
}
