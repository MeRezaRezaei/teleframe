<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfUsersSendPaidMessagesStar;

class TfUsersSendPaidMessagesStarFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfUsersSendPaidMessagesStar::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'send_paid_messages_stars' => fake()->unique()->randomNumber(8),
        ];
    }
}
