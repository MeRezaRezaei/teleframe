<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputStorePaymentPurposeInputStorePaymentPremiumGiftCode (inputStorePaymentPremiumGiftCode). */
final class TlInputStorePaymentPurposeInputStorePaymentPremiumGiftCodeFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputStorePaymentPurposeInputStorePaymentPremiumGiftCode> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputStorePaymentPurposeInputStorePaymentPremiumGiftCode::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'boost_peer' => 1002,
            'currency' => 'currency-3',
            'amount' => 1004,
            'message' => 1005,
        ];
    }
}
