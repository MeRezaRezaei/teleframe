<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlRequestedPeerRequestedPeerChannel (requestedPeerChannel). */
final class TlRequestedPeerRequestedPeerChannelFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRequestedPeerRequestedPeerChannel> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRequestedPeerRequestedPeerChannel::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'channel_id' => 1002,
            'title' => 'title-3',
            'username' => 'username-4',
            'photo' => 1005,
        ];
    }
}
