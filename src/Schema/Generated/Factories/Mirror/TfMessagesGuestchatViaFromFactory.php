<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfMessagesGuestchatViaFrom;

class TfMessagesGuestchatViaFromFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfMessagesGuestchatViaFrom::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'guestchat_via_from_type' => fake()->numberBetween(1, 3),
            'guestchat_via_from_id' => fake()->unique()->randomNumber(8),
        ];
    }
}
