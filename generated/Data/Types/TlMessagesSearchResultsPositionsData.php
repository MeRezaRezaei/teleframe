<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messages.searchResultsPositions of messages.SearchResultsPositions.
 */
final class TlMessagesSearchResultsPositionsData extends TlMessagesSearchResultsPositionsAbstractData
{
    public function __construct(
    public int $count,
    public array $positions,
    ) {
    }
}
