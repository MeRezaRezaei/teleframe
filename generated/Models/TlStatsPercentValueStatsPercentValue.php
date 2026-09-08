<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for statsPercentValue of StatsPercentValue (crc32 cbce2fe0). */
final class TlStatsPercentValueStatsPercentValue extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_stats_percent_value_stats_percent_value';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'part' => 'float',
        'total' => 'float',
    ];
}
