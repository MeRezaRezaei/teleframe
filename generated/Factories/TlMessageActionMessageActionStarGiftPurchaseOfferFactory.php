<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessageActionMessageActionStarGiftPurchaseOffer (messageActionStarGiftPurchaseOffer). */
final class TlMessageActionMessageActionStarGiftPurchaseOfferFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionStarGiftPurchaseOffer> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionStarGiftPurchaseOffer::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'accepted' => true,
            'declined' => true,
            'gift' => 1004,
            'price' => 1005,
            'expires_at' => 6,
        ];
    }
}
