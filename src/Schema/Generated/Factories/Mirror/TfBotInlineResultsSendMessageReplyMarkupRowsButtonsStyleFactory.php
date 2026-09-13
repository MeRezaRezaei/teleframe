<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfBotInlineResultsSendMessageReplyMarkupRowsButtonsStyle;

class TfBotInlineResultsSendMessageReplyMarkupRowsButtonsStyleFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfBotInlineResultsSendMessageReplyMarkupRowsButtonsStyle::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'bg_primary' => fake()->boolean(),
            'bg_danger' => fake()->boolean(),
            'bg_success' => fake()->boolean(),
            'icon' => fake()->unique()->randomNumber(8),
        ];
    }
}
