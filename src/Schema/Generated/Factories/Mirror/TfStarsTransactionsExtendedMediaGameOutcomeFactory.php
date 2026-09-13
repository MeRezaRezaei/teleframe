<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfStarsTransactionsExtendedMediaGameOutcome;

class TfStarsTransactionsExtendedMediaGameOutcomeFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfStarsTransactionsExtendedMediaGameOutcome::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'seed' => fake()->word(),
            'stake_ton_amount' => fake()->unique()->randomNumber(8),
            'ton_amount' => fake()->unique()->randomNumber(8),
        ];
    }
}
