<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfMessagesReplyMarkupRowsButtonsPeerType;

class TfMessagesReplyMarkupRowsButtonsPeerTypeFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfMessagesReplyMarkupRowsButtonsPeerType::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'constructor' => fake()->word(),
            'bot' => fake()->boolean(),
            'premium' => fake()->boolean(),
            'creator' => fake()->boolean(),
            'bot_participant' => fake()->boolean(),
            'has_username' => fake()->boolean(),
            'forum' => fake()->boolean(),
            'bot_managed' => fake()->boolean(),
            'suggested_name' => fake()->word(),
            'suggested_username' => fake()->word(),
        ];
    }
}
