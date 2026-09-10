<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlRequestPeerTypeRequestPeerTypeBroadcast (requestPeerTypeBroadcast). */
final class TlRequestPeerTypeRequestPeerTypeBroadcastFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRequestPeerTypeRequestPeerTypeBroadcast> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRequestPeerTypeRequestPeerTypeBroadcast::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'creator' => true,
            'has_username' => 1003,
            'user_admin_rights' => 1004,
            'bot_admin_rights' => 1005,
        ];
    }
}
