<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfStarsTransactionsExtendedMediaResultsSolutionEntity;

class TfStarsTransactionsExtendedMediaResultsSolutionEntityFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfStarsTransactionsExtendedMediaResultsSolutionEntity::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'position' => fake()->numberBetween(1, 3),
            'constructor' => fake()->word(),
            'offset' => fake()->numberBetween(0, 2147483647),
            'length' => fake()->numberBetween(0, 2147483647),
            'language' => fake()->word(),
            'url' => fake()->word(),
            'user_id' => fake()->unique()->randomNumber(8),
            'document_id' => fake()->unique()->randomNumber(8),
            'collapsed' => fake()->boolean(),
            'relative' => fake()->boolean(),
            'short_time' => fake()->boolean(),
            'long_time' => fake()->boolean(),
            'short_date' => fake()->boolean(),
            'long_date' => fake()->boolean(),
            'day_of_week' => fake()->boolean(),
            'date' => fake()->numberBetween(0, 2147483647),
            'old_text' => fake()->word(),
        ];
    }
}
