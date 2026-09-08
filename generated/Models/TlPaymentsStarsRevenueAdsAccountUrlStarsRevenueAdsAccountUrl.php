<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for payments.starsRevenueAdsAccountUrl of payments.StarsRevenueAdsAccountUrl (crc32 394e7f21). */
final class TlPaymentsStarsRevenueAdsAccountUrlStarsRevenueAdsAccountUrl extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_payments_stars_revenue_ads_account_url_sta_1943787d2312';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'url' => 'string',
    ];
}
