<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for statsGroupTopAdmin of StatsGroupTopAdmin.
 */
final class StatsGroupTopAdminData extends TlStatsGroupTopAdminAbstractData
{
    public function __construct(
    public int $userId,
    public int $deleted,
    public int $kicked,
    public int $banned,
    ) {
    }
}
