<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfBotInlineResultsSendMessageRichMessageBlock;

class TfBotInlineResultsSendMessageRichMessageBlockFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfBotInlineResultsSendMessageRichMessageBlock::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'position' => fake()->numberBetween(1, 3),
            'constructor' => fake()->word(),
            'published_date' => fake()->numberBetween(0, 2147483647),
            'language' => fake()->word(),
            'name' => fake()->word(),
            'spoiler' => fake()->boolean(),
            'photo_id' => fake()->unique()->randomNumber(8),
            'url' => fake()->word(),
            'webpage_id' => fake()->unique()->randomNumber(8),
            'autoplay' => fake()->boolean(),
            'loop' => fake()->boolean(),
            'video_id' => fake()->unique()->randomNumber(8),
            'full_width' => fake()->boolean(),
            'allow_scrolling' => fake()->boolean(),
            'html' => fake()->word(),
            'poster_photo_id' => fake()->unique()->randomNumber(8),
            'w' => fake()->numberBetween(0, 2147483647),
            'h' => fake()->numberBetween(0, 2147483647),
            'author_photo_id' => fake()->unique()->randomNumber(8),
            'author' => fake()->word(),
            'date' => fake()->numberBetween(0, 2147483647),
            'audio_id' => fake()->unique()->randomNumber(8),
            'bordered' => fake()->boolean(),
            'striped' => fake()->boolean(),
            'reversed' => fake()->boolean(),
            'start' => fake()->numberBetween(0, 2147483647),
            'type' => fake()->word(),
            'open' => fake()->boolean(),
            'zoom' => fake()->numberBetween(0, 2147483647),
            'source' => fake()->word(),
        ];
    }
}
