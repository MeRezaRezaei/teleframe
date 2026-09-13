<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfChatsMigratedTo;

class TfChatsMigratedToFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfChatsMigratedTo::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'constructor' => fake()->word(),
            'channel_id' => fake()->unique()->randomNumber(8),
            'access_hash' => fake()->unique()->randomNumber(8),
            'msg_id' => fake()->numberBetween(0, 2147483647),
        ];
    }
}
