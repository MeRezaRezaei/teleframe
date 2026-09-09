<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for starsSubscriptionPricing of StarsSubscriptionPricing (crc32 05416d58). */
final class TlStarsSubscriptionPricingStarsSubscriptionPricing extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_stars_subscription_pricing_stars_subscription_pricing';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'period' => 'int',
        'amount' => 'int',
    ];
}
