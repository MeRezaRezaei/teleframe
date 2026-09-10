<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessageActionMessageActionPrizeStars (messageActionPrizeStars). */
final class TlMessageActionMessageActionPrizeStarsFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionPrizeStars> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionPrizeStars::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'unclaimed' => true,
            'stars' => 1003,
            'transaction_id' => 'transaction_id-4',
            'boost_peer' => 1005,
            'giveaway_msg_id' => 6,
        ];
    }
}
