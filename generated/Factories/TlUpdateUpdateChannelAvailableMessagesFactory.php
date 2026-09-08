<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUpdateUpdateChannelAvailableMessages (updateChannelAvailableMessages). */
final class TlUpdateUpdateChannelAvailableMessagesFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateChannelAvailableMessages> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateChannelAvailableMessages::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'channel_id' => 1001,
            'available_min_id' => 2,
        ];
    }
}
