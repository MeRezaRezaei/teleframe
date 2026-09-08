<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlStarGiftAuctionStateStarGiftAuctionState (starGiftAuctionState). */
final class TlStarGiftAuctionStateStarGiftAuctionStateFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftAuctionStateStarGiftAuctionState> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftAuctionStateStarGiftAuctionState::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'version' => 1,
            'start_date' => 2,
            'end_date' => 3,
            'min_bid_amount' => 1004,
            'next_round_at' => 5,
            'last_gift_num' => 6,
            'gifts_left' => 7,
            'current_round' => 8,
            'total_rounds' => 9,
        ];
    }
}
