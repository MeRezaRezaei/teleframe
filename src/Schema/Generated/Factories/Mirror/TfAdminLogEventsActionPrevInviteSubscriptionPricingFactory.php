<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror\TfAdminLogEventsActionPrevInviteSubscriptionPricing;

class TfAdminLogEventsActionPrevInviteSubscriptionPricingFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = TfAdminLogEventsActionPrevInviteSubscriptionPricing::class;

    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(8),
            'period' => fake()->numberBetween(0, 2147483647),
            'amount' => fake()->unique()->randomNumber(8),
        ];
    }
}
