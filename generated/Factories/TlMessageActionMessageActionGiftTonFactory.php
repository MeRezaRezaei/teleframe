<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessageActionMessageActionGiftTon (messageActionGiftTon). */
final class TlMessageActionMessageActionGiftTonFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionGiftTon> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionGiftTon::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'currency' => 'currency-2',
            'amount' => 1003,
            'crypto_currency' => 'crypto_currency-4',
            'crypto_amount' => 1005,
            'transaction_id' => 'transaction_id-6',
        ];
    }
}
