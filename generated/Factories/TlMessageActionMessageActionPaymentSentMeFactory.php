<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessageActionMessageActionPaymentSentMe (messageActionPaymentSentMe). */
final class TlMessageActionMessageActionPaymentSentMeFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionPaymentSentMe> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionPaymentSentMe::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'recurring_init' => true,
            'recurring_used' => true,
            'currency' => 'currency-4',
            'total_amount' => 1005,
            'payload' => 'Ynl0ZXMtNg==',
            'info' => (string) new \Symfony\Component\Uid\UuidV7(),
            'shipping_option_id' => 'shipping_option_id-8',
            'charge' => (string) new \Symfony\Component\Uid\UuidV7(),
            'subscription_until_date' => 10,
        ];
    }
}
