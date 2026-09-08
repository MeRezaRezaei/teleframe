<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputStorePaymentPurposeInputStorePaymentStarsTopup (inputStorePaymentStarsTopup). */
final class TlInputStorePaymentPurposeInputStorePaymentStarsTopupFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputStorePaymentPurposeInputStorePaymentStarsTopup> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputStorePaymentPurposeInputStorePaymentStarsTopup::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'stars' => 1002,
            'currency' => 'currency-3',
            'amount' => 1004,
            'spend_purpose_peer' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
