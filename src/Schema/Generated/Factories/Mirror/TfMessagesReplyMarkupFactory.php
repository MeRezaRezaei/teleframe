<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfMessagesReplyMarkup;

class TfMessagesReplyMarkupFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfMessagesReplyMarkup::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'constructor' => fake()->word(),
            'selective' => fake()->boolean(),
            'single_use' => fake()->boolean(),
            'placeholder' => fake()->word(),
            'resize' => fake()->boolean(),
            'persistent' => fake()->boolean(),
        ];
    }
}
