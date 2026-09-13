<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfStarsTransactionsExtendedMediaPoll;

class TfStarsTransactionsExtendedMediaPollFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfStarsTransactionsExtendedMediaPoll::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'closed' => fake()->boolean(),
            'public_voters' => fake()->boolean(),
            'multiple_choice' => fake()->boolean(),
            'quiz' => fake()->boolean(),
            'open_answers' => fake()->boolean(),
            'revoting_disabled' => fake()->boolean(),
            'shuffle_answers' => fake()->boolean(),
            'hide_results_until_close' => fake()->boolean(),
            'creator' => fake()->boolean(),
            'subscribers_only' => fake()->boolean(),
            'close_period' => fake()->numberBetween(0, 2147483647),
            'close_date' => fake()->numberBetween(0, 2147483647),
            'hash' => fake()->unique()->randomNumber(8),
        ];
    }
}
