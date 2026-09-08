<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputPaymentCredentialsInputPaymentCredentialsApplePay (inputPaymentCredentialsApplePay). */
final class TlInputPaymentCredentialsInputPaymentCredentialsApplePayFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputPaymentCredentialsInputPaymentCredentialsApplePay> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputPaymentCredentialsInputPaymentCredentialsApplePay::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'payment_data' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
