<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfMessagesFromRank;

class TfMessagesFromRankFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfMessagesFromRank::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'from_rank' => fake()->word(),
        ];
    }
}
