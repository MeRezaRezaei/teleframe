<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfMessagesRestrictionReason;

class TfMessagesRestrictionReasonFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfMessagesRestrictionReason::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'position' => fake()->numberBetween(1, 3),
            'platform' => fake()->word(),
            'reason' => fake()->word(),
            'text' => fake()->word(),
        ];
    }
}
