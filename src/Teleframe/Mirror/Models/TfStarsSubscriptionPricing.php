<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * 1:1 pricing child — the required StarsSubscriptionPricing union
 * (starsSubscriptionPricing | starsSubscriptionPricingToday).
 */
final class TfStarsSubscriptionPricing extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_stars_subscriptions_pricing';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'period' => 'int',
        'amount' => 'int',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(TfStarsSubscription::class, 'id', 'id');
    }
}
