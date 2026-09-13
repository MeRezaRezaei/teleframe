<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfDialogsDraftSuggestedPostPrice;

class TfDialogsDraftSuggestedPostPriceFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfDialogsDraftSuggestedPostPrice::class;

    public function definition(): array
    {
        return [
            'peer_type' => fake()->numberBetween(1, 3),
            'peer_id' => fake()->unique()->randomNumber(8),
            'constructor' => fake()->word(),
            'amount' => fake()->unique()->randomNumber(8),
            'nanos' => fake()->numberBetween(0, 2147483647),
        ];
    }
}
