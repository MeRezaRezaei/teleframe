<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfDocument;

class TfDocumentFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfDocument::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'constructor' => fake()->word(),
            'access_hash' => fake()->unique()->randomNumber(8),
            'file_reference' => fake()->word(),
            'date' => fake()->numberBetween(0, 2147483647),
            'mime_type' => fake()->word(),
            'size' => fake()->unique()->randomNumber(8),
            'dc_id' => fake()->numberBetween(0, 2147483647),
        ];
    }
}
