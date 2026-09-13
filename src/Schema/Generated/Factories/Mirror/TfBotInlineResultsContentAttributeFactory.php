<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfBotInlineResultsContentAttribute;

class TfBotInlineResultsContentAttributeFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfBotInlineResultsContentAttribute::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'position' => fake()->numberBetween(1, 3),
            'constructor' => fake()->word(),
            'w' => fake()->numberBetween(0, 2147483647),
            'h' => fake()->numberBetween(0, 2147483647),
            'mask' => fake()->boolean(),
            'alt' => fake()->word(),
            'round_message' => fake()->boolean(),
            'supports_streaming' => fake()->boolean(),
            'nosound' => fake()->boolean(),
            'duration' => fake()->randomFloat(6, -90, 90),
            'preload_prefix_size' => fake()->numberBetween(0, 2147483647),
            'video_start_ts' => fake()->randomFloat(6, -90, 90),
            'video_codec' => fake()->word(),
            'voice' => fake()->boolean(),
            'title' => fake()->word(),
            'performer' => fake()->word(),
            'waveform' => fake()->word(),
            'file_name' => fake()->word(),
            'free' => fake()->boolean(),
            'text_color' => fake()->boolean(),
        ];
    }
}
