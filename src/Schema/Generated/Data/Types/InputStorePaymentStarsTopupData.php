<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputStorePaymentStarsTopup of InputStorePaymentPurpose.
 */
final class InputStorePaymentStarsTopupData extends TlInputStorePaymentPurposeAbstractData
{
    public function __construct(
    public int $flags,
    public int $stars,
    public string $currency,
    public int $amount,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputPeerAbstractData $spendPurposePeer,
    ) {
    }
}
