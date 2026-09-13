<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfStarsSubscription;

class TfStarsSubscriptionFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfStarsSubscription::class;

    public function definition(): array
    {
        return [
            'id' => fake()->word(),
            'peer_type' => fake()->numberBetween(1, 3),
            'peer_id' => fake()->unique()->randomNumber(8),
            'until_date' => fake()->numberBetween(0, 2147483647),
            'canceled' => fake()->boolean(),
            'can_refulfill' => fake()->boolean(),
            'missing_balance' => fake()->boolean(),
            'bot_canceled' => fake()->boolean(),
        ];
    }
}
