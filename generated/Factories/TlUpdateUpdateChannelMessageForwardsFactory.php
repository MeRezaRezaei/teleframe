<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdateUpdateChannelMessageForwards (updateChannelMessageForwards). */
final class TlUpdateUpdateChannelMessageForwardsFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateChannelMessageForwards> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateChannelMessageForwards::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'channel_id' => 1001,
            'tl_id' => 2,
            'forwards' => 3,
        ];
    }
}
