<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfMessagesSuggestedPost;

class TfMessagesSuggestedPostFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfMessagesSuggestedPost::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'accepted' => fake()->boolean(),
            'rejected' => fake()->boolean(),
            'schedule_date' => fake()->numberBetween(0, 2147483647),
        ];
    }
}
