<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfBotInlineResultsSendMessageRichMessageBlocksGeo;

class TfBotInlineResultsSendMessageRichMessageBlocksGeoFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfBotInlineResultsSendMessageRichMessageBlocksGeo::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'constructor' => fake()->word(),
            'long' => fake()->randomFloat(6, -90, 90),
            'lat' => fake()->randomFloat(6, -90, 90),
            'access_hash' => fake()->unique()->randomNumber(8),
            'accuracy_radius' => fake()->numberBetween(0, 2147483647),
        ];
    }
}
