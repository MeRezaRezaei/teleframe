<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfTheme;

class TfThemeFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfTheme::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'access_hash' => fake()->unique()->randomNumber(8),
            'slug' => fake()->word(),
            'title' => fake()->word(),
            'creator' => fake()->boolean(),
            'default' => fake()->boolean(),
            'for_chat' => fake()->boolean(),
        ];
    }
}
