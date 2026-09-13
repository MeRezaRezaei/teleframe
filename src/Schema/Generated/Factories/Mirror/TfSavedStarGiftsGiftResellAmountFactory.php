<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfSavedStarGiftsGiftResellAmount;

class TfSavedStarGiftsGiftResellAmountFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfSavedStarGiftsGiftResellAmount::class;

    public function definition(): array
    {
        return [
            'saved_star_gift_id' => fake()->unique()->randomNumber(8),
            'position' => fake()->numberBetween(1, 3),
            'constructor' => fake()->word(),
            'amount' => fake()->unique()->randomNumber(8),
            'nanos' => fake()->numberBetween(0, 2147483647),
        ];
    }
}
