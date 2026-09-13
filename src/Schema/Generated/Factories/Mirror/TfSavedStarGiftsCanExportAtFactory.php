<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfSavedStarGiftsCanExportAt;

class TfSavedStarGiftsCanExportAtFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfSavedStarGiftsCanExportAt::class;

    public function definition(): array
    {
        return [
            'saved_star_gift_id' => fake()->unique()->randomNumber(8),
            'can_export_at' => fake()->numberBetween(0, 2147483647),
        ];
    }
}
