<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for statsAbsValueAndPrev of StatsAbsValueAndPrev.
 */
final class StatsAbsValueAndPrevData extends TlStatsAbsValueAndPrevAbstractData
{
    public function __construct(
    public float $current,
    public float $previous,
    ) {
    }
}
