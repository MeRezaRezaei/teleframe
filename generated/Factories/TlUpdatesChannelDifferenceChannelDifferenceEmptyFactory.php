<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdatesChannelDifferenceChannelDifferenceEmpty (updates.channelDifferenceEmpty). */
final class TlUpdatesChannelDifferenceChannelDifferenceEmptyFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdatesChannelDifferenceChannelDifferenceEmpty> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdatesChannelDifferenceChannelDifferenceEmpty::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'final' => true,
            'pts' => 3,
            'timeout' => 4,
        ];
    }
}
