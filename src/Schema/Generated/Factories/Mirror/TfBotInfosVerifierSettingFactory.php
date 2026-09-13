<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfBotInfosVerifierSetting;

class TfBotInfosVerifierSettingFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfBotInfosVerifierSetting::class;

    public function definition(): array
    {
        return [
            'bot_info_id' => fake()->unique()->randomNumber(8),
            'can_modify_custom_description' => fake()->boolean(),
            'icon' => fake()->unique()->randomNumber(8),
            'company' => fake()->word(),
            'custom_description' => fake()->word(),
        ];
    }
}
