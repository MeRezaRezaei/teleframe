<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for statsPercentValue of StatsPercentValue.
 */
final class StatsPercentValueData extends TlStatsPercentValueAbstractData
{
    public function __construct(
    public float $part,
    public float $total,
    ) {
    }
}
