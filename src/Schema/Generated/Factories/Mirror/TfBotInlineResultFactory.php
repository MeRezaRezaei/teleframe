<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfBotInlineResult;

class TfBotInlineResultFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfBotInlineResult::class;

    public function definition(): array
    {
        return [
            'id' => fake()->word(),
            'constructor' => fake()->word(),
            'type' => fake()->word(),
        ];
    }
}
