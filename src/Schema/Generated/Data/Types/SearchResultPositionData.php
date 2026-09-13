<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for searchResultPosition of SearchResultsPosition.
 */
final class SearchResultPositionData extends TlSearchResultsPositionAbstractData
{
    public function __construct(
    public int $msgId,
    public int $date,
    public int $offset,
    ) {
    }
}
