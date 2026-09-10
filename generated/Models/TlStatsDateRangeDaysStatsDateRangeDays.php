<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for statsDateRangeDays of StatsDateRangeDays (crc32 b637edaf). */
final class TlStatsDateRangeDaysStatsDateRangeDays extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_stats_date_range_days_stats_date_range_days';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'min_date' => 'int',
        'max_date' => 'int',
    ];
}
