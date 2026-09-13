<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfUsersStatu;

class TfUsersStatuFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfUsersStatu::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'constructor' => fake()->word(),
            'expires' => fake()->numberBetween(0, 2147483647),
            'was_online' => fake()->numberBetween(0, 2147483647),
            'by_me' => fake()->boolean(),
        ];
    }
}
