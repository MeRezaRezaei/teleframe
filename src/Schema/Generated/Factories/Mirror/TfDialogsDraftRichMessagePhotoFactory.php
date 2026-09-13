<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfDialogsDraftRichMessagePhoto;

class TfDialogsDraftRichMessagePhotoFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfDialogsDraftRichMessagePhoto::class;

    public function definition(): array
    {
        return [
            'peer_type' => fake()->numberBetween(1, 3),
            'peer_id' => fake()->unique()->randomNumber(8),
            'position' => fake()->numberBetween(1, 3),
            'constructor' => fake()->word(),
            'id' => fake()->unique()->randomNumber(8),
            'has_stickers' => fake()->boolean(),
            'access_hash' => fake()->unique()->randomNumber(8),
            'file_reference' => fake()->word(),
            'date' => fake()->numberBetween(0, 2147483647),
            'dc_id' => fake()->numberBetween(0, 2147483647),
        ];
    }
}
