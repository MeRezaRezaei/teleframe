<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlPrepaidGiveawayPrepaidGiveaway (prepaidGiveaway). */
final class TlPrepaidGiveawayPrepaidGiveawayFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPrepaidGiveawayPrepaidGiveaway> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPrepaidGiveawayPrepaidGiveaway::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tl_id' => 1001,
            'months' => 2,
            'quantity' => 3,
            'date' => 4,
        ];
    }
}
