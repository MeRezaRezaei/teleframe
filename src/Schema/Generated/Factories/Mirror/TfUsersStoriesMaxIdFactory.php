<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfUsersStoriesMaxId;

class TfUsersStoriesMaxIdFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfUsersStoriesMaxId::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'live' => fake()->boolean(),
            'max_id' => fake()->numberBetween(0, 2147483647),
        ];
    }
}
