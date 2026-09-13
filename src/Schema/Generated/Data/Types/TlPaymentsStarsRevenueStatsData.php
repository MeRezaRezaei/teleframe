<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for payments.starsRevenueStats of payments.StarsRevenueStats.
 */
final class TlPaymentsStarsRevenueStatsData extends TlPaymentsStarsRevenueStatsAbstractData
{
    public function __construct(
    public int $flags,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStatsGraphAbstractData $topHoursGraph,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStatsGraphAbstractData $revenueGraph,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStarsRevenueStatusAbstractData $status,
    public float $usdRate,
    ) {
    }
}
