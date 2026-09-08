<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputStorePaymentPurposeInputStorePaymentPremiumGiveaway (inputStorePaymentPremiumGiveaway). */
final class TlInputStorePaymentPurposeInputStorePaymentPremiumGiveawayFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputStorePaymentPurposeInputStorePaymentPremiumGiveaway> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputStorePaymentPurposeInputStorePaymentPremiumGiveaway::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'only_new_subscribers' => true,
            'winners_are_visible' => true,
            'boost_peer' => (string) new \Symfony\Component\Uid\UuidV7(),
            'prize_description' => 'prize_description-5',
            'random_id' => 1006,
            'until_date' => 7,
            'currency' => 'currency-8',
            'amount' => 1009,
        ];
    }
}
