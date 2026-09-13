<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfDialogFilter;

class TfDialogFilterFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfDialogFilter::class;

    public function definition(): array
    {
        return [
            'id' => fake()->numberBetween(0, 2147483647),
            'constructor' => fake()->word(),
            'contacts' => fake()->boolean(),
            'non_contacts' => fake()->boolean(),
            'groups' => fake()->boolean(),
            'broadcasts' => fake()->boolean(),
            'bots' => fake()->boolean(),
            'exclude_muted' => fake()->boolean(),
            'exclude_read' => fake()->boolean(),
            'exclude_archived' => fake()->boolean(),
            'title_noanimate' => fake()->boolean(),
        ];
    }
}
