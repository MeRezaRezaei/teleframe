<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputChannelInputChannel (inputChannel). */
final class TlInputChannelInputChannelFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputChannelInputChannel> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputChannelInputChannel::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'channel_id' => 1001,
            'access_hash' => 1002,
        ];
    }
}
