<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlStarGiftAuctionUserStateStarGiftAuctionUserState (starGiftAuctionUserState). */
final class TlStarGiftAuctionUserStateStarGiftAuctionUserStateFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftAuctionUserStateStarGiftAuctionUserState> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftAuctionUserStateStarGiftAuctionUserState::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'returned' => true,
            'bid_amount' => 1003,
            'bid_date' => 4,
            'min_bid_amount' => 1005,
            'bid_peer' => 1006,
            'acquired_count' => 7,
        ];
    }
}
