<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfReaction;

class TfReactionFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfReaction::class;

    public function definition(): array
    {
        return [
            'reaction_id' => fake()->unique()->randomNumber(8),
            'constructor' => fake()->word(),
            'emoticon' => fake()->word(),
        ];
    }
}
