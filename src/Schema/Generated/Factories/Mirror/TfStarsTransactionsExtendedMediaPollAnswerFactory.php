<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfStarsTransactionsExtendedMediaPollAnswer;

class TfStarsTransactionsExtendedMediaPollAnswerFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfStarsTransactionsExtendedMediaPollAnswer::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'position' => fake()->numberBetween(1, 3),
            'option' => fake()->word(),
            'added_by_type' => fake()->numberBetween(1, 3),
            'added_by_id' => fake()->unique()->randomNumber(8),
            'date' => fake()->numberBetween(0, 2147483647),
        ];
    }
}
