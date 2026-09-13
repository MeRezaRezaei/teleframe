<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for starGiftUpgradePrice of StarGiftUpgradePrice.
 */
final class StarGiftUpgradePriceData extends TlStarGiftUpgradePriceAbstractData
{
    public function __construct(
    public int $date,
    public int $upgradeStars,
    ) {
    }
}
