<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfMessagesViaBusinessBotId;

class TfMessagesViaBusinessBotIdFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfMessagesViaBusinessBotId::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'via_business_bot_id' => fake()->unique()->randomNumber(8),
        ];
    }
}
