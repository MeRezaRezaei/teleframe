<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsStarsRevenueStatsStarsRevenueStats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateStarsRevenueStatus;

/** Anchor model for TL type StarsRevenueStatus (spec §4.1). */
final class TlStarsRevenueStatus extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_stars_revenue_status_stars_revenue_status';

    protected $guarded = [];

    public function status(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateStarsRevenueStatus::class, 'status');
    }
    public function statusPaymentsStarsRevenueStats(): HasMany
    {
        return $this->hasMany(TlPaymentsStarsRevenueStatsStarsRevenueStats::class, 'status');
    }
}
