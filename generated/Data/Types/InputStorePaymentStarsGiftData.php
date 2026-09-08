<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputStorePaymentStarsGift of InputStorePaymentPurpose.
 */
final class InputStorePaymentStarsGiftData extends TlInputStorePaymentPurposeAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputUserAbstractData $userId,
    public int $stars,
    public string $currency,
    public int $amount,
    ) {
    }
}
