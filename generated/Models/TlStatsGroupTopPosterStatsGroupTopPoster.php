<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for statsGroupTopPoster of StatsGroupTopPoster (crc32 9d04af9b). */
final class TlStatsGroupTopPosterStatsGroupTopPoster extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_stats_group_top_poster_stats_group_top_poster';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'user_id' => 'int',
        'messages' => 'int',
        'avg_chars' => 'int',
    ];
}
