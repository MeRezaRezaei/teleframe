<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlPaymentRequestedInfoPaymentRequestedInfo (paymentRequestedInfo). */
final class TlPaymentRequestedInfoPaymentRequestedInfoFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentRequestedInfoPaymentRequestedInfo> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentRequestedInfoPaymentRequestedInfo::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'name' => 'name-2',
            'phone' => 'phone-3',
            'email' => 'email-4',
            'shipping_address' => 1005,
        ];
    }
}
