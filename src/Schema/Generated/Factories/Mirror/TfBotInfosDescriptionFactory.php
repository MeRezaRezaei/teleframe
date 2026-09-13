<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfBotInfosDescription;

class TfBotInfosDescriptionFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfBotInfosDescription::class;

    public function definition(): array
    {
        return [
            'bot_info_id' => fake()->unique()->randomNumber(8),
            'description' => fake()->word(),
        ];
    }
}
