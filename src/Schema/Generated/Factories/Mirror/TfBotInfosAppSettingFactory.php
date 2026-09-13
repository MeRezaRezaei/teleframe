<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfBotInfosAppSetting;

class TfBotInfosAppSettingFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfBotInfosAppSetting::class;

    public function definition(): array
    {
        return [
            'bot_info_id' => fake()->unique()->randomNumber(8),
            'placeholder_path' => fake()->word(),
            'background_color' => fake()->numberBetween(0, 2147483647),
            'background_dark_color' => fake()->numberBetween(0, 2147483647),
            'header_color' => fake()->numberBetween(0, 2147483647),
            'header_dark_color' => fake()->numberBetween(0, 2147483647),
        ];
    }
}
