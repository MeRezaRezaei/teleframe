<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type SearchResultsPosition (spec §4.1). */
final class TlSearchResultsPosition extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_search_results_position_search_result_position';

    protected $guarded = [];
}
