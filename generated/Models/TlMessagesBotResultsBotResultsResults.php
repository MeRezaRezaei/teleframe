<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param results (table tl_messages_bot_results_bot_results__results). */
final class TlMessagesBotResultsBotResultsResults extends TlAnchorModel
{
    protected $table = 'tl_messages_bot_results_bot_results__results';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
