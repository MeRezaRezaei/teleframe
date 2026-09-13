<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfSavedStarGiftsGiftPeerColor;

class TfSavedStarGiftsGiftPeerColorFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfSavedStarGiftsGiftPeerColor::class;

    public function definition(): array
    {
        return [
            'saved_star_gift_id' => fake()->unique()->randomNumber(8),
            'constructor' => fake()->word(),
            'color' => fake()->numberBetween(0, 2147483647),
            'background_emoji_id' => fake()->unique()->randomNumber(8),
            'collectible_id' => fake()->unique()->randomNumber(8),
            'gift_emoji_id' => fake()->unique()->randomNumber(8),
            'accent_color' => fake()->numberBetween(0, 2147483647),
            'dark_accent_color' => fake()->numberBetween(0, 2147483647),
        ];
    }
}
