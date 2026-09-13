<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfAdminLogEvent;

class TfAdminLogEventFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfAdminLogEvent::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'date' => fake()->numberBetween(0, 2147483647),
            'user_id' => fake()->unique()->randomNumber(8),
        ];
    }
}
