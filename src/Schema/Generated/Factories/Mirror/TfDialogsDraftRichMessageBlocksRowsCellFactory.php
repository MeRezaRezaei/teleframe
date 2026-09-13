<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfDialogsDraftRichMessageBlocksRowsCell;

class TfDialogsDraftRichMessageBlocksRowsCellFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfDialogsDraftRichMessageBlocksRowsCell::class;

    public function definition(): array
    {
        return [
            'peer_type' => fake()->numberBetween(1, 3),
            'peer_id' => fake()->unique()->randomNumber(8),
            'position' => fake()->numberBetween(1, 3),
            'header' => fake()->boolean(),
            'align_center' => fake()->boolean(),
            'align_right' => fake()->boolean(),
            'valign_middle' => fake()->boolean(),
            'valign_bottom' => fake()->boolean(),
            'colspan' => fake()->numberBetween(0, 2147483647),
            'rowspan' => fake()->numberBetween(0, 2147483647),
        ];
    }
}
