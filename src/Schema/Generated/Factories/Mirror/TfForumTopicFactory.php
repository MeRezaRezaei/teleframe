<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfForumTopic;

class TfForumTopicFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfForumTopic::class;

    public function definition(): array
    {
        return [
            'id' => fake()->numberBetween(0, 2147483647),
            'constructor' => fake()->word(),
        ];
    }
}
