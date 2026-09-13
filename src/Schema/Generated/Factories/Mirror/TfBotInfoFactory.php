<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfBotInfo;

class TfBotInfoFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfBotInfo::class;

    public function definition(): array
    {
        return [
            'bot_info_id' => fake()->unique()->randomNumber(8),
            'has_preview_medias' => fake()->boolean(),
        ];
    }
}
