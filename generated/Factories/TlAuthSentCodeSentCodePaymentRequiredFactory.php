<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlAuthSentCodeSentCodePaymentRequired (auth.sentCodePaymentRequired). */
final class TlAuthSentCodeSentCodePaymentRequiredFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAuthSentCodeSentCodePaymentRequired> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAuthSentCodeSentCodePaymentRequired::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'store_product' => 'store_product-1',
            'phone_code_hash' => 'phone_code_hash-2',
            'support_email_address' => 'support_email_address-3',
            'support_email_subject' => 'support_email_subject-4',
            'premium_days' => 5,
            'currency' => 'currency-6',
            'amount' => 1007,
        ];
    }
}
