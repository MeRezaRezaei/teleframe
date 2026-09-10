<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPremiumBoostsStatusBoostsStatus;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsBroadcastStatsBroadcastStats;

/** Anchor model for TL type StatsPercentValue (spec §4.1). */
final class TlStatsPercentValue extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_stats_percent_value_stats_percent_value';

    protected $guarded = [];

    public function enabledNotifications(): HasMany
    {
        return $this->hasMany(TlStatsBroadcastStatsBroadcastStats::class, 'enabled_notifications');
    }
    public function premiumAudience(): HasMany
    {
        return $this->hasMany(TlPremiumBoostsStatusBoostsStatus::class, 'premium_audience');
    }
}
