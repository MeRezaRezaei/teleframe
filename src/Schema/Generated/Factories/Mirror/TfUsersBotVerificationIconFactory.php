<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfUsersBotVerificationIcon;

class TfUsersBotVerificationIconFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfUsersBotVerificationIcon::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'bot_verification_icon' => fake()->unique()->randomNumber(8),
        ];
    }
}
