<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfDialogsDraftSuggestedPost;

class TfDialogsDraftSuggestedPostFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfDialogsDraftSuggestedPost::class;

    public function definition(): array
    {
        return [
            'peer_type' => fake()->numberBetween(1, 3),
            'peer_id' => fake()->unique()->randomNumber(8),
            'accepted' => fake()->boolean(),
            'rejected' => fake()->boolean(),
            'schedule_date' => fake()->numberBetween(0, 2147483647),
        ];
    }
}
