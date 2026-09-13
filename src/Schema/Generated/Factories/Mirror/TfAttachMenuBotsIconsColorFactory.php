<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfAttachMenuBotsIconsColor;

class TfAttachMenuBotsIconsColorFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfAttachMenuBotsIconsColor::class;

    public function definition(): array
    {
        return [
            'bot_id' => fake()->unique()->randomNumber(8),
            'position' => fake()->numberBetween(1, 3),
            'name' => fake()->word(),
            'color' => fake()->numberBetween(0, 2147483647),
        ];
    }
}
