<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfFolder;

class TfFolderFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfFolder::class;

    public function definition(): array
    {
        return [
            'id' => fake()->numberBetween(0, 2147483647),
            'title' => fake()->word(),
            'autofill_new_broadcasts' => fake()->boolean(),
            'autofill_public_groups' => fake()->boolean(),
            'autofill_new_correspondents' => fake()->boolean(),
        ];
    }
}
