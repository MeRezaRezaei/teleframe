<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfAttachMenuBotsPeerType;

class TfAttachMenuBotsPeerTypeFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfAttachMenuBotsPeerType::class;

    public function definition(): array
    {
        return [
            'bot_id' => fake()->unique()->randomNumber(8),
            'position' => fake()->numberBetween(1, 3),
            'constructor' => fake()->word(),
        ];
    }
}
