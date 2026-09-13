<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfStickerSet;

class TfStickerSetFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfStickerSet::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'access_hash' => fake()->unique()->randomNumber(8),
            'title' => fake()->word(),
            'short_name' => fake()->word(),
            'count' => fake()->numberBetween(0, 2147483647),
            'hash' => fake()->numberBetween(0, 2147483647),
            'archived' => fake()->boolean(),
            'official' => fake()->boolean(),
            'masks' => fake()->boolean(),
            'emojis' => fake()->boolean(),
            'text_color' => fake()->boolean(),
            'channel_emoji_status' => fake()->boolean(),
            'creator' => fake()->boolean(),
        ];
    }
}
