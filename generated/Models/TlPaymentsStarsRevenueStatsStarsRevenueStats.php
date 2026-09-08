<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for payments.starsRevenueStats of payments.StarsRevenueStats (crc32 6c207376). */
final class TlPaymentsStarsRevenueStatsStarsRevenueStats extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_payments_stars_revenue_stats_stars_revenue_stats';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'top_hours_graph' => 'string',
        'revenue_graph' => 'string',
        'status' => 'string',
        'usd_rate' => 'float',
    ];
}
