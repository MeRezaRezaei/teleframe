<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlPaymentsPaymentReceiptPaymentReceipt (payments.paymentReceipt). */
final class TlPaymentsPaymentReceiptPaymentReceiptFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsPaymentReceiptPaymentReceipt> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsPaymentReceiptPaymentReceipt::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'date' => 2,
            'bot_id' => 1003,
            'provider_id' => 1004,
            'title' => 'title-5',
            'description' => 'description-6',
            'photo' => 1007,
            'invoice' => 1008,
            'info' => 1009,
            'shipping' => 1010,
            'tip_amount' => 1011,
            'currency' => 'currency-12',
            'total_amount' => 1013,
            'credentials_title' => 'credentials_title-14',
        ];
    }
}
