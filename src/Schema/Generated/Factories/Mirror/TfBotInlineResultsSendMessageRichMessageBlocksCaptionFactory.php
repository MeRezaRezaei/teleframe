<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfBotInlineResultsSendMessageRichMessageBlocksCaption;

class TfBotInlineResultsSendMessageRichMessageBlocksCaptionFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfBotInlineResultsSendMessageRichMessageBlocksCaption::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'constructor' => fake()->word(),
            'text' => fake()->word(),
            'url' => fake()->word(),
            'webpage_id' => fake()->unique()->randomNumber(8),
            'email' => fake()->word(),
            'phone' => fake()->word(),
            'document_id' => fake()->unique()->randomNumber(8),
            'w' => fake()->numberBetween(0, 2147483647),
            'h' => fake()->numberBetween(0, 2147483647),
            'name' => fake()->word(),
            'source' => fake()->word(),
            'alt' => fake()->word(),
            'user_id' => fake()->unique()->randomNumber(8),
            'relative' => fake()->boolean(),
            'short_time' => fake()->boolean(),
            'long_time' => fake()->boolean(),
            'short_date' => fake()->boolean(),
            'long_date' => fake()->boolean(),
            'day_of_week' => fake()->boolean(),
            'date' => fake()->numberBetween(0, 2147483647),
        ];
    }
}
