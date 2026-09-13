<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfThemesSettingsMessageColor;

class TfThemesSettingsMessageColorFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfThemesSettingsMessageColor::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'position' => fake()->numberBetween(1, 3),
            'value' => fake()->numberBetween(0, 2147483647),
        ];
    }
}
