<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfDialogFiltersExcludePeer;

class TfDialogFiltersExcludePeerFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfDialogFiltersExcludePeer::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'position' => fake()->numberBetween(1, 3),
            'constructor' => fake()->word(),
            'chat_id' => fake()->unique()->randomNumber(8),
            'user_id' => fake()->unique()->randomNumber(8),
            'access_hash' => fake()->unique()->randomNumber(8),
            'channel_id' => fake()->unique()->randomNumber(8),
            'msg_id' => fake()->numberBetween(0, 2147483647),
        ];
    }
}
