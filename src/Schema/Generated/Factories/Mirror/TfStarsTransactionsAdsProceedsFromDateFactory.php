<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfStarsTransactionsAdsProceedsFromDate;

class TfStarsTransactionsAdsProceedsFromDateFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfStarsTransactionsAdsProceedsFromDate::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'ads_proceeds_from_date' => fake()->numberBetween(0, 2147483647),
        ];
    }
}
