<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfQuickReplie;

class TfQuickReplieFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfQuickReplie::class;

    public function definition(): array
    {
        return [
            'shortcut_id' => fake()->numberBetween(0, 2147483647),
            'shortcut' => fake()->word(),
            'top_message' => fake()->numberBetween(0, 2147483647),
            'count' => fake()->numberBetween(0, 2147483647),
        ];
    }
}
