<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfStarsTransactionsExtendedMediaResult;

class TfStarsTransactionsExtendedMediaResultFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfStarsTransactionsExtendedMediaResult::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'min' => fake()->boolean(),
            'has_unread_votes' => fake()->boolean(),
            'can_view_stats' => fake()->boolean(),
            'total_voters' => fake()->numberBetween(0, 2147483647),
            'recent_voters_type' => fake()->numberBetween(1, 3),
            'recent_voters_id' => fake()->unique()->randomNumber(8),
            'solution' => fake()->word(),
        ];
    }
}
