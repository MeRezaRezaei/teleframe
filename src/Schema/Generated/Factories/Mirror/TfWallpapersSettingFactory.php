<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfWallpapersSetting;

class TfWallpapersSettingFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfWallpapersSetting::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'blur' => fake()->boolean(),
            'motion' => fake()->boolean(),
            'background_color' => fake()->numberBetween(0, 2147483647),
            'second_background_color' => fake()->numberBetween(0, 2147483647),
            'third_background_color' => fake()->numberBetween(0, 2147483647),
            'fourth_background_color' => fake()->numberBetween(0, 2147483647),
            'intensity' => fake()->numberBetween(0, 2147483647),
            'rotation' => fake()->numberBetween(0, 2147483647),
            'emoticon' => fake()->word(),
        ];
    }
}
