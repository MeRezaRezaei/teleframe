<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlRequestPeerTypeRequestPeerTypeChat (requestPeerTypeChat). */
final class TlRequestPeerTypeRequestPeerTypeChatFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRequestPeerTypeRequestPeerTypeChat> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRequestPeerTypeRequestPeerTypeChat::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'creator' => true,
            'bot_participant' => true,
            'has_username' => 1004,
            'forum' => 1005,
            'user_admin_rights' => 1006,
            'bot_admin_rights' => 1007,
        ];
    }
}
