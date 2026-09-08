<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for premium.boostsList of premium.BoostsList.
 */
final class TlPremiumBoostsListData extends TlPremiumBoostsListAbstractData
{
    public function __construct(
    public int $flags,
    public int $count,
    public array $boosts,
    public ?string $nextOffset,
    public array $users,
    ) {
    }
}
