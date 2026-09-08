<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlChatlistsChatlistInviteChatlistInvite (chatlists.chatlistInvite). */
final class TlChatlistsChatlistInviteChatlistInviteFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatlistsChatlistInviteChatlistInvite> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatlistsChatlistInviteChatlistInvite::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'title_noanimate' => true,
            'title' => (string) new \Symfony\Component\Uid\UuidV7(),
            'emoticon' => 'emoticon-4',
        ];
    }
}
