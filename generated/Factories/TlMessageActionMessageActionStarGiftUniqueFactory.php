<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessageActionMessageActionStarGiftUnique (messageActionStarGiftUnique). */
final class TlMessageActionMessageActionStarGiftUniqueFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionStarGiftUnique> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionStarGiftUnique::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'upgrade' => true,
            'transferred' => true,
            'saved' => true,
            'refunded' => true,
            'prepaid_upgrade' => true,
            'assigned' => true,
            'from_offer' => true,
            'craft' => true,
            'gift' => (string) new \Symfony\Component\Uid\UuidV7(),
            'can_export_at' => 11,
            'transfer_stars' => 1012,
            'from_id' => (string) new \Symfony\Component\Uid\UuidV7(),
            'peer' => (string) new \Symfony\Component\Uid\UuidV7(),
            'saved_id' => 1015,
            'resale_amount' => (string) new \Symfony\Component\Uid\UuidV7(),
            'can_transfer_at' => 17,
            'can_resell_at' => 18,
            'drop_original_details_stars' => 1019,
            'can_craft_at' => 20,
        ];
    }
}
