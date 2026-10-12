<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for statsGroupTopInviter of StatsGroupTopInviter.
 */
final class StatsGroupTopInviterData extends TlStatsGroupTopInviterAbstractData
{
    public function __construct(
    public int $userId,
    public int $invitations,
    ) {
    }
}
