<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfUsersEmojiStatu;

class TfUsersEmojiStatuFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfUsersEmojiStatu::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'constructor' => fake()->word(),
            'document_id' => fake()->unique()->randomNumber(8),
            'until' => fake()->numberBetween(0, 2147483647),
            'collectible_id' => fake()->unique()->randomNumber(8),
            'title' => fake()->word(),
            'slug' => fake()->word(),
            'pattern_document_id' => fake()->unique()->randomNumber(8),
            'center_color' => fake()->numberBetween(0, 2147483647),
            'edge_color' => fake()->numberBetween(0, 2147483647),
            'pattern_color' => fake()->numberBetween(0, 2147483647),
            'text_color' => fake()->numberBetween(0, 2147483647),
        ];
    }
}
