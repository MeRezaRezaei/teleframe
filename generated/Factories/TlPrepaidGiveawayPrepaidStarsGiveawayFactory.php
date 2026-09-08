<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlPrepaidGiveawayPrepaidStarsGiveaway (prepaidStarsGiveaway). */
final class TlPrepaidGiveawayPrepaidStarsGiveawayFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPrepaidGiveawayPrepaidStarsGiveaway> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPrepaidGiveawayPrepaidStarsGiveaway::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tl_id' => 1001,
            'stars' => 1002,
            'quantity' => 3,
            'boosts' => 4,
            'date' => 5,
        ];
    }
}
