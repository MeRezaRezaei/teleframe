<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for starsSubscriptionPricing of StarsSubscriptionPricing.
 */
final class StarsSubscriptionPricingData extends TlStarsSubscriptionPricingAbstractData
{
    public function __construct(
    public int $period,
    public int $amount,
    ) {
    }
}
