<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for statsDateRangeDays of StatsDateRangeDays.
 */
final class StatsDateRangeDaysData extends TlStatsDateRangeDaysAbstractData
{
    public function __construct(
    public int $minDate,
    public int $maxDate,
    ) {
    }
}
