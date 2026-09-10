<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsBroadcastStatsBroadcastStats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsMegagroupStatsMegagroupStats;

/** Anchor model for TL type StatsDateRangeDays (spec §4.1). */
final class TlStatsDateRangeDays extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_stats_date_range_days_stats_date_range_days';

    protected $guarded = [];

    public function period(): HasMany
    {
        return $this->hasMany(TlStatsBroadcastStatsBroadcastStats::class, 'period');
    }
    public function periodStatsMegagroupStats(): HasMany
    {
        return $this->hasMany(TlStatsMegagroupStatsMegagroupStats::class, 'period');
    }
}
