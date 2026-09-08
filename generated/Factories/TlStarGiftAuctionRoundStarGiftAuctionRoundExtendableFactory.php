<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlStarGiftAuctionRoundStarGiftAuctionRoundExtendable (starGiftAuctionRoundExtendable). */
final class TlStarGiftAuctionRoundStarGiftAuctionRoundExtendableFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftAuctionRoundStarGiftAuctionRoundExtendable> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftAuctionRoundStarGiftAuctionRoundExtendable::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'num' => 1,
            'duration' => 2,
            'extend_top' => 3,
            'extend_window' => 4,
        ];
    }
}
