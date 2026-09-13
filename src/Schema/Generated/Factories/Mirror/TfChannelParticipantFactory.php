<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfChannelParticipant;

class TfChannelParticipantFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfChannelParticipant::class;

    public function definition(): array
    {
        return [
            'user_id' => fake()->unique()->randomNumber(8),
            'constructor' => fake()->word(),
            'date' => fake()->numberBetween(0, 2147483647),
        ];
    }
}
