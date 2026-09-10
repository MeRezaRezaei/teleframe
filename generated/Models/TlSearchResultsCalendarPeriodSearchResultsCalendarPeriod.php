<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for searchResultsCalendarPeriod of SearchResultsCalendarPeriod (crc32 c9b0539f). */
final class TlSearchResultsCalendarPeriodSearchResultsCalendarPeriod extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_search_results_calendar_period_search_resu_9116af4a75f6';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'date' => 'int',
        'min_msg_id' => 'int',
        'max_msg_id' => 'int',
        'count' => 'int',
    ];
}
