<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfStarsTransactionsExtendedMediaExtendedMedia;

class TfStarsTransactionsExtendedMediaExtendedMediaFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfStarsTransactionsExtendedMediaExtendedMedia::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'position' => fake()->numberBetween(1, 3),
            'constructor' => fake()->word(),
            'w' => fake()->numberBetween(0, 2147483647),
            'h' => fake()->numberBetween(0, 2147483647),
            'video_duration' => fake()->numberBetween(0, 2147483647),
        ];
    }
}
