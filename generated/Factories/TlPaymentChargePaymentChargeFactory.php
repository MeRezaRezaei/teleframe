<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlPaymentChargePaymentCharge (paymentCharge). */
final class TlPaymentChargePaymentChargeFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentChargePaymentCharge> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentChargePaymentCharge::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tl_id' => 'id-1',
            'provider_charge_id' => 'provider_charge_id-2',
        ];
    }
}
