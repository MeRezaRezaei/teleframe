<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfUsersAccessHash;

class TfUsersAccessHashFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfUsersAccessHash::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'access_hash' => fake()->unique()->randomNumber(8),
        ];
    }
}
