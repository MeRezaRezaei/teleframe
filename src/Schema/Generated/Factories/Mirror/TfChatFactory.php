<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfChat;

class TfChatFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfChat::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'constructor' => fake()->word(),
            'title' => fake()->word(),
            'participants_count' => fake()->numberBetween(0, 2147483647),
            'date' => fake()->numberBetween(0, 2147483647),
            'version' => fake()->numberBetween(0, 2147483647),
            'creator' => fake()->boolean(),
            'left' => fake()->boolean(),
            'deactivated' => fake()->boolean(),
            'call_active' => fake()->boolean(),
            'call_not_empty' => fake()->boolean(),
            'noforwards' => fake()->boolean(),
        ];
    }
}
