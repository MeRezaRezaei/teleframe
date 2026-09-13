<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfMessageActionsUser;

class TfMessageActionsUserFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfMessageActionsUser::class;

    public function definition(): array
    {
        return [
            'message_action_id' => fake()->unique()->randomNumber(8),
            'position' => fake()->numberBetween(1, 3),
            'value' => fake()->unique()->randomNumber(8),
        ];
    }
}
