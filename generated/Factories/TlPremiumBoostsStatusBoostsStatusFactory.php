<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlPremiumBoostsStatusBoostsStatus (premium.boostsStatus). */
final class TlPremiumBoostsStatusBoostsStatusFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPremiumBoostsStatusBoostsStatus> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPremiumBoostsStatusBoostsStatus::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'my_boost' => true,
            'level' => 3,
            'current_level_boosts' => 4,
            'boosts' => 5,
            'gift_boosts' => 6,
            'next_level_boosts' => 7,
            'premium_audience' => (string) new \Symfony\Component\Uid\UuidV7(),
            'boost_url' => 'boost_url-9',
        ];
    }
}
