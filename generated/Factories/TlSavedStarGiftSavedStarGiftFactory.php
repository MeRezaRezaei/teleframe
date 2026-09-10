<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlSavedStarGiftSavedStarGift (savedStarGift). */
final class TlSavedStarGiftSavedStarGiftFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSavedStarGiftSavedStarGift> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSavedStarGiftSavedStarGift::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'name_hidden' => true,
            'unsaved' => true,
            'refunded' => true,
            'can_upgrade' => true,
            'pinned_to_top' => true,
            'upgrade_separate' => true,
            'from_id' => 1008,
            'date' => 9,
            'gift' => 1010,
            'message' => 1011,
            'msg_id' => 12,
            'saved_id' => 1013,
            'convert_stars' => 1014,
            'upgrade_stars' => 1015,
            'can_export_at' => 16,
            'transfer_stars' => 1017,
            'can_transfer_at' => 18,
            'can_resell_at' => 19,
            'prepaid_upgrade_hash' => 'prepaid_upgrade_hash-20',
            'drop_original_details_stars' => 1021,
            'gift_num' => 22,
            'can_craft_at' => 23,
        ];
    }
}
