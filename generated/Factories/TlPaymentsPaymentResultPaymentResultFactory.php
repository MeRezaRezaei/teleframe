<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlPaymentsPaymentResultPaymentResult (payments.paymentResult). */
final class TlPaymentsPaymentResultPaymentResultFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsPaymentResultPaymentResult> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsPaymentResultPaymentResult::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'updates' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
