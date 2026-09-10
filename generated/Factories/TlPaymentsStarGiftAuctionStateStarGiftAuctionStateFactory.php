<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlPaymentsStarGiftAuctionStateStarGiftAuctionState (payments.starGiftAuctionState). */
final class TlPaymentsStarGiftAuctionStateStarGiftAuctionStateFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsStarGiftAuctionStateStarGiftAuctionState> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsStarGiftAuctionStateStarGiftAuctionState::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'gift' => 1001,
            'state' => 1002,
            'user_state' => 1003,
            'timeout' => 4,
        ];
    }
}
