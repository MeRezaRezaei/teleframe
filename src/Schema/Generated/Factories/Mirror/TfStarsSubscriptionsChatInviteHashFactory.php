<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfStarsSubscriptionsChatInviteHash;

class TfStarsSubscriptionsChatInviteHashFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfStarsSubscriptionsChatInviteHash::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'chat_invite_hash' => fake()->word(),
        ];
    }
}
