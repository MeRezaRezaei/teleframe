<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfMessagesService;

class TfMessagesServiceFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfMessagesService::class;

    public function definition(): array
    {
        return [
            'id' => fake()->numberBetween(0, 2147483647),
            'peer_id_type' => fake()->numberBetween(1, 3),
            'peer_id_id' => fake()->unique()->randomNumber(8),
            'date' => fake()->numberBetween(0, 2147483647),
            'out' => fake()->boolean(),
            'mentioned' => fake()->boolean(),
            'media_unread' => fake()->boolean(),
            'reactions_are_possible' => fake()->boolean(),
            'silent' => fake()->boolean(),
            'post' => fake()->boolean(),
            'legacy' => fake()->boolean(),
        ];
    }
}
