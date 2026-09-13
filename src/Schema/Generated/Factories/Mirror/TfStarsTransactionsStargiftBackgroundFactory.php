<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfStarsTransactionsStargiftBackground;

class TfStarsTransactionsStargiftBackgroundFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfStarsTransactionsStargiftBackground::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'center_color' => fake()->numberBetween(0, 2147483647),
            'edge_color' => fake()->numberBetween(0, 2147483647),
            'text_color' => fake()->numberBetween(0, 2147483647),
        ];
    }
}
