<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for statsGroupTopPoster of StatsGroupTopPoster.
 */
final class StatsGroupTopPosterData extends TlStatsGroupTopPosterAbstractData
{
    public function __construct(
    public int $userId,
    public int $messages,
    public int $avgChars,
    ) {
    }
}
