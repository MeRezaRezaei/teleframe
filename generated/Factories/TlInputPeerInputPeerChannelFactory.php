<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlInputPeerInputPeerChannel (inputPeerChannel). */
final class TlInputPeerInputPeerChannelFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputPeerInputPeerChannel> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputPeerInputPeerChannel::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'channel_id' => 1001,
            'access_hash' => 1002,
        ];
    }
}
