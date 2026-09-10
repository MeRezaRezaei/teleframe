<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputStorePaymentPurposeInputStorePaymentStarsGift (inputStorePaymentStarsGift). */
final class TlInputStorePaymentPurposeInputStorePaymentStarsGiftFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputStorePaymentPurposeInputStorePaymentStarsGift> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputStorePaymentPurposeInputStorePaymentStarsGift::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => 1001,
            'stars' => 1002,
            'currency' => 'currency-3',
            'amount' => 1004,
        ];
    }
}
