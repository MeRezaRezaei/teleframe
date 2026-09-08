<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlPaymentsPaymentFormPaymentFormStarGift (payments.paymentFormStarGift). */
final class TlPaymentsPaymentFormPaymentFormStarGiftFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsPaymentFormPaymentFormStarGift> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsPaymentFormPaymentFormStarGift::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'form_id' => 1001,
            'invoice' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
