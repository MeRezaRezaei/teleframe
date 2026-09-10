<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessageActionMessageActionGiftCode (messageActionGiftCode). */
final class TlMessageActionMessageActionGiftCodeFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionGiftCode> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionGiftCode::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'via_giveaway' => true,
            'unclaimed' => true,
            'boost_peer' => 1004,
            'days' => 5,
            'slug' => 'slug-6',
            'currency' => 'currency-7',
            'amount' => 1008,
            'crypto_currency' => 'crypto_currency-9',
            'crypto_amount' => 1010,
            'message' => 1011,
        ];
    }
}
