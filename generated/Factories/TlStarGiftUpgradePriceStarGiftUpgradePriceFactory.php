<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlStarGiftUpgradePriceStarGiftUpgradePrice (starGiftUpgradePrice). */
final class TlStarGiftUpgradePriceStarGiftUpgradePriceFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftUpgradePriceStarGiftUpgradePrice> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftUpgradePriceStarGiftUpgradePrice::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'date' => 1,
            'upgrade_stars' => 1002,
        ];
    }
}
