<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlChatFullChatFull (chatFull). */
final class TlChatFullChatFullFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatFullChatFull> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatFullChatFull::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'can_set_username' => true,
            'has_scheduled' => true,
            'translations_disabled' => true,
            'tl_id' => 1005,
            'about' => 'about-6',
            'participants' => (string) new \Symfony\Component\Uid\UuidV7(),
            'chat_photo' => (string) new \Symfony\Component\Uid\UuidV7(),
            'notify_settings' => (string) new \Symfony\Component\Uid\UuidV7(),
            'exported_invite' => (string) new \Symfony\Component\Uid\UuidV7(),
            'pinned_msg_id' => 11,
            'folder_id' => 12,
            'call' => (string) new \Symfony\Component\Uid\UuidV7(),
            'ttl_period' => 14,
            'groupcall_default_join_as' => (string) new \Symfony\Component\Uid\UuidV7(),
            'theme_emoticon' => 'theme_emoticon-16',
            'requests_pending' => 17,
            'available_reactions' => (string) new \Symfony\Component\Uid\UuidV7(),
            'reactions_limit' => 19,
        ];
    }
}
