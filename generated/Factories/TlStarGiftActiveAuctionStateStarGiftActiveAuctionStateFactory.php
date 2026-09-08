<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlStarGiftActiveAuctionStateStarGiftActiveAuctionState (starGiftActiveAuctionState). */
final class TlStarGiftActiveAuctionStateStarGiftActiveAuctionStateFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftActiveAuctionStateStarGiftActiveAuctionState> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftActiveAuctionStateStarGiftActiveAuctionState::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'gift' => (string) new \Symfony\Component\Uid\UuidV7(),
            'state' => (string) new \Symfony\Component\Uid\UuidV7(),
            'user_state' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
