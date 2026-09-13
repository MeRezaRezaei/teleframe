<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for payments.validatedRequestedInfo of payments.ValidatedRequestedInfo.
 */
final class TlPaymentsValidatedRequestedInfoData extends TlPaymentsValidatedRequestedInfoAbstractData
{
    public function __construct(
    public int $flags,
    public ?string $id,
    public ?array $shippingOptions,
    ) {
    }
}
