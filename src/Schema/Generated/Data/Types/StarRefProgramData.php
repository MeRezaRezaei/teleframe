<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for starRefProgram of StarRefProgram.
 */
final class StarRefProgramData extends TlStarRefProgramAbstractData
{
    public function __construct(
    public int $flags,
    public int $botId,
    public int $commissionPermille,
    public ?int $durationMonths,
    public ?int $endDate,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStarsAmountAbstractData $dailyRevenuePerUser,
    ) {
    }
}
