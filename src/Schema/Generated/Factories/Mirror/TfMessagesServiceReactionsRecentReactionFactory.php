<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfMessagesServiceReactionsRecentReaction;

class TfMessagesServiceReactionsRecentReactionFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfMessagesServiceReactionsRecentReaction::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'position' => fake()->numberBetween(1, 3),
            'big' => fake()->boolean(),
            'unread' => fake()->boolean(),
            'my' => fake()->boolean(),
            'peer_id_type' => fake()->numberBetween(1, 3),
            'peer_id_id' => fake()->unique()->randomNumber(8),
            'date' => fake()->numberBetween(0, 2147483647),
        ];
    }
}
