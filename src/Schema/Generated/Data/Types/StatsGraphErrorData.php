<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for statsGraphError of StatsGraph.
 */
final class StatsGraphErrorData extends TlStatsGraphAbstractData
{
    public function __construct(
    public string $error,
    ) {
    }
}
