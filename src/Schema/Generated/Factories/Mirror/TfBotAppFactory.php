<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfBotApp;

class TfBotAppFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfBotApp::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'constructor' => fake()->word(),
            'access_hash' => fake()->unique()->randomNumber(8),
            'short_name' => fake()->word(),
            'title' => fake()->word(),
            'description' => fake()->word(),
            'hash' => fake()->unique()->randomNumber(8),
        ];
    }
}
