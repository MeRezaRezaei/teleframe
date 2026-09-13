<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfBotInlineResultsSendMessageRichMessageBlocksArticle;

class TfBotInlineResultsSendMessageRichMessageBlocksArticleFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfBotInlineResultsSendMessageRichMessageBlocksArticle::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'position' => fake()->numberBetween(1, 3),
            'url' => fake()->word(),
            'webpage_id' => fake()->unique()->randomNumber(8),
            'title' => fake()->word(),
            'description' => fake()->word(),
            'photo_id' => fake()->unique()->randomNumber(8),
            'author' => fake()->word(),
            'published_date' => fake()->numberBetween(0, 2147483647),
        ];
    }
}
