<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdateUpdateStarGiftAuctionUserState (updateStarGiftAuctionUserState). */
final class TlUpdateUpdateStarGiftAuctionUserStateFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateStarGiftAuctionUserState> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateStarGiftAuctionUserState::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'gift_id' => 1001,
            'user_state' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
