<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlPremiumSubscriptionOptionPremiumSubscriptionOption (premiumSubscriptionOption). */
final class TlPremiumSubscriptionOptionPremiumSubscriptionOptionFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPremiumSubscriptionOptionPremiumSubscriptionOption> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPremiumSubscriptionOptionPremiumSubscriptionOption::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'tl_current' => true,
            'can_purchase_upgrade' => true,
            'transaction' => 'transaction-4',
            'months' => 5,
            'currency' => 'currency-6',
            'amount' => 1007,
            'bot_url' => 'bot_url-8',
            'store_product' => 'store_product-9',
        ];
    }
}
