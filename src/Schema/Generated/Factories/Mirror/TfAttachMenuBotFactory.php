<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfAttachMenuBot;

class TfAttachMenuBotFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfAttachMenuBot::class;

    public function definition(): array
    {
        return [
            'bot_id' => fake()->unique()->randomNumber(8),
            'short_name' => fake()->word(),
            'inactive' => fake()->boolean(),
            'has_settings' => fake()->boolean(),
            'request_write_access' => fake()->boolean(),
            'show_in_attach_menu' => fake()->boolean(),
            'show_in_side_menu' => fake()->boolean(),
            'side_menu_disclaimer_needed' => fake()->boolean(),
        ];
    }
}
