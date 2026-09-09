<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param periods (table tl_messages_search_results_calendar_search_re_7e53ac3da76d). */
final class TlMessagesSearchResultsCalendarSearchReB1534ec0e56fPeriods extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_messages_search_results_calendar_search_re_7e53ac3da76d';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
