<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for searchResultPosition of SearchResultsPosition (crc32 7f648b67). */
final class TlSearchResultsPositionSearchResultPosition extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_search_results_position_search_result_position';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'msg_id' => 'int',
        'date' => 'int',
        'tl_offset' => 'int',
    ];
}
