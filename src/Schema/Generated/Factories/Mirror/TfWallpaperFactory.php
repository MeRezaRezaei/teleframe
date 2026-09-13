<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfWallpaper;

class TfWallpaperFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfWallpaper::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'constructor' => fake()->word(),
            'access_hash' => fake()->unique()->randomNumber(8),
            'slug' => fake()->word(),
            'creator' => fake()->boolean(),
            'default' => fake()->boolean(),
            'pattern' => fake()->boolean(),
            'dark' => fake()->boolean(),
        ];
    }
}
