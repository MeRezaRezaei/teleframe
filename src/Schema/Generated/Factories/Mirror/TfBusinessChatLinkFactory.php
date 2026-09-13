<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfBusinessChatLink;

class TfBusinessChatLinkFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfBusinessChatLink::class;

    public function definition(): array
    {
        return [
            'business_chat_link_id' => fake()->unique()->randomNumber(8),
            'link' => fake()->word(),
            'message' => fake()->word(),
            'views' => fake()->numberBetween(0, 2147483647),
        ];
    }
}
