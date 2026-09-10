<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type messages.SearchResultsPositions (spec §4.1). */
final class TlMessagesSearchResultsPositions extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_messages_search_results_positions_search_r_d401856bd5e6';

    protected $guarded = [];
}
