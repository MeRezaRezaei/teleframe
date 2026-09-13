<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfMessagesReplyMarkupRowsButton;

class TfMessagesReplyMarkupRowsButtonFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfMessagesReplyMarkupRowsButton::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'position' => fake()->numberBetween(1, 3),
            'constructor' => fake()->word(),
            'text' => fake()->word(),
            'url' => fake()->word(),
            'requires_password' => fake()->boolean(),
            'data' => fake()->word(),
            'same_peer' => fake()->boolean(),
            'query' => fake()->word(),
            'fwd_text' => fake()->word(),
            'button_id' => fake()->numberBetween(0, 2147483647),
            'quiz' => fake()->boolean(),
            'user_id' => fake()->unique()->randomNumber(8),
            'max_quantity' => fake()->numberBetween(0, 2147483647),
            'copy_text' => fake()->word(),
        ];
    }
}
