<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfMessagesServiceReaction;

class TfMessagesServiceReactionFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfMessagesServiceReaction::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'min' => fake()->boolean(),
            'can_see_list' => fake()->boolean(),
            'reactions_as_tags' => fake()->boolean(),
        ];
    }
}
