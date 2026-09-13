<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfDialogsNotifySetting;

class TfDialogsNotifySettingFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfDialogsNotifySetting::class;

    public function definition(): array
    {
        return [
            'peer_type' => fake()->numberBetween(1, 3),
            'peer_id' => fake()->unique()->randomNumber(8),
            'show_previews' => fake()->boolean(),
            'silent' => fake()->boolean(),
            'mute_until' => fake()->numberBetween(0, 2147483647),
            'stories_muted' => fake()->boolean(),
            'stories_hide_sender' => fake()->boolean(),
        ];
    }
}
