<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfThemesSetting;

class TfThemesSettingFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfThemesSetting::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'position' => fake()->numberBetween(1, 3),
            'message_colors_animated' => fake()->boolean(),
            'accent_color' => fake()->numberBetween(0, 2147483647),
            'outbox_accent_color' => fake()->numberBetween(0, 2147483647),
        ];
    }
}
