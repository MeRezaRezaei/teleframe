<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfStarsTransactionsStargiftAttribute;

class TfStarsTransactionsStargiftAttributeFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfStarsTransactionsStargiftAttribute::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'position' => fake()->numberBetween(1, 3),
            'constructor' => fake()->word(),
            'crafted' => fake()->boolean(),
            'name' => fake()->word(),
            'backdrop_id' => fake()->numberBetween(0, 2147483647),
            'center_color' => fake()->numberBetween(0, 2147483647),
            'edge_color' => fake()->numberBetween(0, 2147483647),
            'pattern_color' => fake()->numberBetween(0, 2147483647),
            'text_color' => fake()->numberBetween(0, 2147483647),
            'sender_id_type' => fake()->numberBetween(1, 3),
            'sender_id_id' => fake()->unique()->randomNumber(8),
            'recipient_id_type' => fake()->numberBetween(1, 3),
            'recipient_id_id' => fake()->unique()->randomNumber(8),
            'date' => fake()->numberBetween(0, 2147483647),
        ];
    }
}
