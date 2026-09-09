<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type payments.StarsRevenueAdsAccountUrl (spec §4.1). */
final class TlPaymentsStarsRevenueAdsAccountUrl extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_payments_stars_revenue_ads_account_url';

    protected $guarded = [];
}
