<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInvoiceInvoice (invoice). */
final class TlInvoiceInvoiceFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInvoiceInvoice> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInvoiceInvoice::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'test' => true,
            'name_requested' => true,
            'phone_requested' => true,
            'email_requested' => true,
            'shipping_address_requested' => true,
            'flexible' => true,
            'phone_to_provider' => true,
            'email_to_provider' => true,
            'recurring' => true,
            'currency' => 'currency-11',
            'max_tip_amount' => 1012,
            'terms_url' => 'terms_url-13',
            'subscription_period' => 14,
        ];
    }
}
