<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfUsersPhoto;

class TfUsersPhotoFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfUsersPhoto::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'constructor' => fake()->word(),
            'has_video' => fake()->boolean(),
            'personal' => fake()->boolean(),
            'photo_id' => fake()->unique()->randomNumber(8),
            'stripped_thumb' => fake()->word(),
            'dc_id' => fake()->numberBetween(0, 2147483647),
        ];
    }
}
