<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfStarsTransactionsExtendedMediaResultsResult;

class TfStarsTransactionsExtendedMediaResultsResultFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfStarsTransactionsExtendedMediaResultsResult::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'position' => fake()->numberBetween(1, 3),
            'chosen' => fake()->boolean(),
            'correct' => fake()->boolean(),
            'option' => fake()->word(),
            'voters' => fake()->numberBetween(0, 2147483647),
            'recent_voters_type' => fake()->numberBetween(1, 3),
            'recent_voters_id' => fake()->unique()->randomNumber(8),
        ];
    }
}
