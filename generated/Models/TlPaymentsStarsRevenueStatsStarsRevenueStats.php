<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarsRevenueStatus;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsGraph;

/** Constructor model for payments.starsRevenueStats of payments.StarsRevenueStats (crc32 6c207376). */
final class TlPaymentsStarsRevenueStatsStarsRevenueStats extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_payments_stars_revenue_stats_stars_revenue_stats';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'usd_rate' => 'float',
    ];

    public function topHoursGraph(): BelongsTo
    {
        return $this->belongsTo(TlStatsGraph::class, 'top_hours_graph');
    }
    public function revenueGraph(): BelongsTo
    {
        return $this->belongsTo(TlStatsGraph::class, 'revenue_graph');
    }
    public function status(): BelongsTo
    {
        return $this->belongsTo(TlStarsRevenueStatus::class, 'status');
    }
}
