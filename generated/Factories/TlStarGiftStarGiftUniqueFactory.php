<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlStarGiftStarGiftUnique (starGiftUnique). */
final class TlStarGiftStarGiftUniqueFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftStarGiftUnique> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftStarGiftUnique::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'require_premium' => true,
            'resale_ton_only' => true,
            'theme_available' => true,
            'burned' => true,
            'crafted' => true,
            'tl_id' => 1007,
            'gift_id' => 1008,
            'title' => 'title-9',
            'slug' => 'slug-10',
            'num' => 11,
            'owner_id' => (string) new \Symfony\Component\Uid\UuidV7(),
            'owner_name' => 'owner_name-13',
            'owner_address' => 'owner_address-14',
            'availability_issued' => 15,
            'availability_total' => 16,
            'gift_address' => 'gift_address-17',
            'released_by' => (string) new \Symfony\Component\Uid\UuidV7(),
            'value_amount' => 1019,
            'value_currency' => 'value_currency-20',
            'value_usd_amount' => 1021,
            'theme_peer' => (string) new \Symfony\Component\Uid\UuidV7(),
            'peer_color' => (string) new \Symfony\Component\Uid\UuidV7(),
            'host_id' => (string) new \Symfony\Component\Uid\UuidV7(),
            'offer_min_stars' => 25,
            'craft_chance_permille' => 26,
        ];
    }
}
