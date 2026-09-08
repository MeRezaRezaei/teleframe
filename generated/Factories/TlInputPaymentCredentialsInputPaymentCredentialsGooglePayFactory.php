<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputPaymentCredentialsInputPaymentCredentialsGooglePay (inputPaymentCredentialsGooglePay). */
final class TlInputPaymentCredentialsInputPaymentCredentialsGooglePayFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputPaymentCredentialsInputPaymentCredentialsGooglePay> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputPaymentCredentialsInputPaymentCredentialsGooglePay::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'payment_token' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
