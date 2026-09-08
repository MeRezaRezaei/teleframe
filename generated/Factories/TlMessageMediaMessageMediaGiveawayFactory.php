<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlMessageMediaMessageMediaGiveaway (messageMediaGiveaway). */
final class TlMessageMediaMessageMediaGiveawayFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMediaMessageMediaGiveaway> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMediaMessageMediaGiveaway::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'only_new_subscribers' => true,
            'winners_are_visible' => true,
            'prize_description' => 'prize_description-4',
            'quantity' => 5,
            'months' => 6,
            'stars' => 1007,
            'until_date' => 8,
        ];
    }
}
