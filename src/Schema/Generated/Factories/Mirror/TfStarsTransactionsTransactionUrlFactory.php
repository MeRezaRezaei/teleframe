<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfStarsTransactionsTransactionUrl;

class TfStarsTransactionsTransactionUrlFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfStarsTransactionsTransactionUrl::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'transaction_url' => fake()->word(),
        ];
    }
}
