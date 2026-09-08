<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessageActionMessageActionGiftPremium (messageActionGiftPremium). */
final class TlMessageActionMessageActionGiftPremiumFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionGiftPremium> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionGiftPremium::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'currency' => 'currency-2',
            'amount' => 1003,
            'days' => 4,
            'crypto_currency' => 'crypto_currency-5',
            'crypto_amount' => 1006,
            'message' => (string) new \Symfony\Component\Uid\UuidV7(),
        ];
    }
}
