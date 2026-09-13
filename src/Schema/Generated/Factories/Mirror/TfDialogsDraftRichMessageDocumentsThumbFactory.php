<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfDialogsDraftRichMessageDocumentsThumb;

class TfDialogsDraftRichMessageDocumentsThumbFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfDialogsDraftRichMessageDocumentsThumb::class;

    public function definition(): array
    {
        return [
            'peer_type' => fake()->numberBetween(1, 3),
            'peer_id' => fake()->unique()->randomNumber(8),
            'position' => fake()->numberBetween(1, 3),
            'constructor' => fake()->word(),
            'type' => fake()->word(),
            'w' => fake()->numberBetween(0, 2147483647),
            'h' => fake()->numberBetween(0, 2147483647),
            'size' => fake()->numberBetween(0, 2147483647),
            'bytes' => fake()->word(),
        ];
    }
}
