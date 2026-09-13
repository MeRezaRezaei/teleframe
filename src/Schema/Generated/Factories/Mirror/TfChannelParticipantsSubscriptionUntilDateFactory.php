<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfChannelParticipantsSubscriptionUntilDate;

class TfChannelParticipantsSubscriptionUntilDateFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfChannelParticipantsSubscriptionUntilDate::class;

    public function definition(): array
    {
        return [
            'user_id' => fake()->unique()->randomNumber(8),
            'subscription_until_date' => fake()->numberBetween(0, 2147483647),
        ];
    }
}
