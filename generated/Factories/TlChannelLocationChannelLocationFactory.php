<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlChannelLocationChannelLocation (channelLocation). */
final class TlChannelLocationChannelLocationFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelLocationChannelLocation> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelLocationChannelLocation::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'geo_point' => 1001,
            'address' => 'address-2',
        ];
    }
}
