<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfAdminLogEventsAction;

class TfAdminLogEventsActionFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfAdminLogEventsAction::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'constructor' => fake()->word(),
            'prev_value' => fake()->word(),
            'new_value' => fake()->word(),
            'join_muted' => fake()->boolean(),
            'via_chatlist' => fake()->boolean(),
            'approved_by' => fake()->unique()->randomNumber(8),
            'user_id' => fake()->unique()->randomNumber(8),
            'prev_rank' => fake()->word(),
            'new_rank' => fake()->word(),
        ];
    }
}
