<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfGroupCall;

class TfGroupCallFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfGroupCall::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'constructor' => fake()->word(),
            'access_hash' => fake()->unique()->randomNumber(8),
            'duration' => fake()->numberBetween(0, 2147483647),
        ];
    }
}
