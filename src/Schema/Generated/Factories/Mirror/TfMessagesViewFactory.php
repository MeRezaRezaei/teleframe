<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfMessagesView;

class TfMessagesViewFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfMessagesView::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'views' => fake()->numberBetween(0, 2147483647),
        ];
    }
}
