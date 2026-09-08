<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlPaymentsPaymentFormPaymentForm (payments.paymentForm). */
final class TlPaymentsPaymentFormPaymentFormFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsPaymentFormPaymentForm> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsPaymentFormPaymentForm::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'can_save_credentials' => true,
            'password_missing' => true,
            'form_id' => 1004,
            'bot_id' => 1005,
            'title' => 'title-6',
            'description' => 'description-7',
            'photo' => (string) new \Symfony\Component\Uid\UuidV7(),
            'invoice' => (string) new \Symfony\Component\Uid\UuidV7(),
            'provider_id' => 1010,
            'url' => 'url-11',
            'native_provider' => 'native_provider-12',
            'native_params' => (string) new \Symfony\Component\Uid\UuidV7(),
            'saved_info' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
