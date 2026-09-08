<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesSearchResultsPositionsSearchRD401856bd5e6Positions;

/** Constructor model for messages.searchResultsPositions of messages.SearchResultsPositions (crc32 53b22baf). */
final class TlMessagesSearchResultsPositionsSearchResultsPositions extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_messages_search_results_positions_search_r_d401856bd5e6';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'count' => 'int',
    ];

    public function positions(): HasMany
    {
        return $this->tlChild(TlMessagesSearchResultsPositionsSearchRD401856bd5e6Positions::class);
    }
}
