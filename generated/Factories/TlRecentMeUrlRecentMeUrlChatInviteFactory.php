<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlRecentMeUrlRecentMeUrlChatInvite (recentMeUrlChatInvite). */
final class TlRecentMeUrlRecentMeUrlChatInviteFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRecentMeUrlRecentMeUrlChatInvite> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRecentMeUrlRecentMeUrlChatInvite::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'url' => 'url-1',
            'chat_invite' => 1002,
        ];
    }
}
