<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfMessageAction;

class TfMessageActionFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfMessageAction::class;

    public function definition(): array
    {
        return [
            'message_action_id' => fake()->unique()->randomNumber(8),
            'constructor' => fake()->word(),
            'title' => fake()->word(),
        ];
    }
}
