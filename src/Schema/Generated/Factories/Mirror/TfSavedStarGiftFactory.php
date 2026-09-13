<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfSavedStarGift;

class TfSavedStarGiftFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfSavedStarGift::class;

    public function definition(): array
    {
        return [
            'saved_star_gift_id' => fake()->unique()->randomNumber(8),
            'date' => fake()->numberBetween(0, 2147483647),
            'name_hidden' => fake()->boolean(),
            'unsaved' => fake()->boolean(),
            'refunded' => fake()->boolean(),
            'can_upgrade' => fake()->boolean(),
            'pinned_to_top' => fake()->boolean(),
            'upgrade_separate' => fake()->boolean(),
        ];
    }
}
