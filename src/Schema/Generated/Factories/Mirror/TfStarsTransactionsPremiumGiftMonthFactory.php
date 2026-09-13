<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfStarsTransactionsPremiumGiftMonth;

class TfStarsTransactionsPremiumGiftMonthFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfStarsTransactionsPremiumGiftMonth::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'premium_gift_months' => fake()->numberBetween(0, 2147483647),
        ];
    }
}
