<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfBusinessChatLinksTitle;

class TfBusinessChatLinksTitleFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfBusinessChatLinksTitle::class;

    public function definition(): array
    {
        return [
            'business_chat_link_id' => fake()->unique()->randomNumber(8),
            'title' => fake()->word(),
        ];
    }
}
