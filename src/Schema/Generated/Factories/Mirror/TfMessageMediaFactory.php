<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfMessageMedia;

class TfMessageMediaFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfMessageMedia::class;

    public function definition(): array
    {
        return [
            'message_media_id' => fake()->unique()->randomNumber(8),
            'constructor' => fake()->word(),
            'spoiler' => fake()->boolean(),
            'live_photo' => fake()->boolean(),
        ];
    }
}
