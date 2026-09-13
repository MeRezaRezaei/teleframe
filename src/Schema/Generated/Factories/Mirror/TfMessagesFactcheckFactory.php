<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfMessagesFactcheck;

class TfMessagesFactcheckFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfMessagesFactcheck::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'need_check' => fake()->boolean(),
            'country' => fake()->word(),
            'hash' => fake()->unique()->randomNumber(8),
        ];
    }
}
