<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfEncryptedChat;

class TfEncryptedChatFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfEncryptedChat::class;

    public function definition(): array
    {
        return [
            'id' => fake()->numberBetween(0, 2147483647),
            'constructor' => fake()->word(),
            'access_hash' => fake()->unique()->randomNumber(8),
            'date' => fake()->numberBetween(0, 2147483647),
            'admin_id' => fake()->unique()->randomNumber(8),
            'participant_id' => fake()->unique()->randomNumber(8),
        ];
    }
}
