<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfDialogsDraftRichMessageDocumentsThumbsSize;

class TfDialogsDraftRichMessageDocumentsThumbsSizeFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfDialogsDraftRichMessageDocumentsThumbsSize::class;

    public function definition(): array
    {
        return [
            'peer_type' => fake()->numberBetween(1, 3),
            'peer_id' => fake()->unique()->randomNumber(8),
            'position' => fake()->numberBetween(1, 3),
            'value' => fake()->numberBetween(0, 2147483647),
        ];
    }
}
