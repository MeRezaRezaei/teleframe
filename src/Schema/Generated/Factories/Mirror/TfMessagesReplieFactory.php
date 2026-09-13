<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfMessagesReplie;

class TfMessagesReplieFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfMessagesReplie::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'comments' => fake()->boolean(),
            'replies' => fake()->numberBetween(0, 2147483647),
            'replies_pts' => fake()->numberBetween(0, 2147483647),
            'recent_repliers_type' => fake()->numberBetween(1, 3),
            'recent_repliers_id' => fake()->unique()->randomNumber(8),
            'channel_id' => fake()->unique()->randomNumber(8),
            'max_id' => fake()->numberBetween(0, 2147483647),
            'read_max_id' => fake()->numberBetween(0, 2147483647),
        ];
    }
}
