<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfUsersBotInlinePlaceholder;

class TfUsersBotInlinePlaceholderFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfUsersBotInlinePlaceholder::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'bot_inline_placeholder' => fake()->word(),
        ];
    }
}
