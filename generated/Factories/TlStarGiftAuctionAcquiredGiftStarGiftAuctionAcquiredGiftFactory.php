<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlStarGiftAuctionAcquiredGiftStarGiftAuctionAcquiredGift (starGiftAuctionAcquiredGift). */
final class TlStarGiftAuctionAcquiredGiftStarGiftAuctionAcquiredGiftFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftAuctionAcquiredGiftStarGiftAuctionAcquiredGift> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftAuctionAcquiredGiftStarGiftAuctionAcquiredGift::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'name_hidden' => true,
            'peer' => (string) new \Symfony\Component\Uid\UuidV7(),
            'date' => 4,
            'bid_amount' => 1005,
            'round' => 6,
            'pos' => 7,
            'message' => (string) new \Symfony\Component\Uid\UuidV7(),
            'gift_num' => 9,
        ];
    }
}
