<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputStorePaymentPurposeInputStorePaymentGiftPremium (inputStorePaymentGiftPremium). */
final class TlInputStorePaymentPurposeInputStorePaymentGiftPremiumFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputStorePaymentPurposeInputStorePaymentGiftPremium> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputStorePaymentPurposeInputStorePaymentGiftPremium::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => 1001,
            'currency' => 'currency-2',
            'amount' => 1003,
        ];
    }
}
