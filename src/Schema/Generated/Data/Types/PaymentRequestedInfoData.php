<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for paymentRequestedInfo of PaymentRequestedInfo.
 */
final class PaymentRequestedInfoData extends TlPaymentRequestedInfoAbstractData
{
    public function __construct(
    public int $flags,
    public ?string $name,
    public ?string $phone,
    public ?string $email,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPostAddressAbstractData $shippingAddress,
    ) {
    }
}
