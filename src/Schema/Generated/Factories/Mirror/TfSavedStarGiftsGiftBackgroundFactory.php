<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfSavedStarGiftsGiftBackground;

class TfSavedStarGiftsGiftBackgroundFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfSavedStarGiftsGiftBackground::class;

    public function definition(): array
    {
        return [
            'saved_star_gift_id' => fake()->unique()->randomNumber(8),
            'center_color' => fake()->numberBetween(0, 2147483647),
            'edge_color' => fake()->numberBetween(0, 2147483647),
            'text_color' => fake()->numberBetween(0, 2147483647),
        ];
    }
}
