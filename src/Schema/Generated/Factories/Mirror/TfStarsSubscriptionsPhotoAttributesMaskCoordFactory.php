<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfStarsSubscriptionsPhotoAttributesMaskCoord;

class TfStarsSubscriptionsPhotoAttributesMaskCoordFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfStarsSubscriptionsPhotoAttributesMaskCoord::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'n' => fake()->numberBetween(0, 2147483647),
            'x' => fake()->randomFloat(6, -90, 90),
            'y' => fake()->randomFloat(6, -90, 90),
            'zoom' => fake()->randomFloat(6, -90, 90),
        ];
    }
}
