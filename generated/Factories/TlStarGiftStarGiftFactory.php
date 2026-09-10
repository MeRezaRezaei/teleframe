<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlStarGiftStarGift (starGift). */
final class TlStarGiftStarGiftFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftStarGift> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftStarGift::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'limited' => true,
            'sold_out' => true,
            'birthday' => true,
            'require_premium' => true,
            'limited_per_user' => true,
            'peer_color_available' => true,
            'auction' => true,
            'tl_id' => 1009,
            'sticker' => 1010,
            'stars' => 1011,
            'availability_remains' => 12,
            'availability_total' => 13,
            'availability_resale' => 1014,
            'convert_stars' => 1015,
            'first_sale_date' => 16,
            'last_sale_date' => 17,
            'upgrade_stars' => 1018,
            'resell_min_stars' => 1019,
            'title' => 'title-20',
            'released_by' => 1021,
            'per_user_total' => 22,
            'per_user_remains' => 23,
            'locked_until_date' => 24,
            'auction_slug' => 'auction_slug-25',
            'gifts_per_round' => 26,
            'auction_start_date' => 27,
            'upgrade_variants' => 28,
            'background' => 1029,
        ];
    }
}
