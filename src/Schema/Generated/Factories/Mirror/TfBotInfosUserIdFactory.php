<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfBotInfosUserId;

class TfBotInfosUserIdFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfBotInfosUserId::class;

    public function definition(): array
    {
        return [
            'bot_info_id' => fake()->unique()->randomNumber(8),
            'user_id' => fake()->unique()->randomNumber(8),
        ];
    }
}
