<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlChatChannel (channel). */
final class TlChatChannelFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatChannel> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatChannel::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'creator' => true,
            'left' => true,
            'broadcast' => true,
            'verified' => true,
            'megagroup' => true,
            'restricted' => true,
            'signatures' => true,
            'min' => true,
            'scam' => true,
            'has_link' => true,
            'has_geo' => true,
            'slowmode_enabled' => true,
            'call_active' => true,
            'call_not_empty' => true,
            'fake' => true,
            'gigagroup' => true,
            'noforwards' => true,
            'join_to_send' => true,
            'join_request' => true,
            'forum' => true,
            'flags2' => 22,
            'stories_hidden' => true,
            'stories_hidden_min' => true,
            'stories_unavailable' => true,
            'signature_profiles' => true,
            'autotranslation' => true,
            'broadcast_messages_allowed' => true,
            'monoforum' => true,
            'forum_tabs' => true,
            'tl_id' => 1031,
            'access_hash' => 1032,
            'title' => 'title-33',
            'username' => 'username-34',
            'photo' => (string) new \Symfony\Component\Uid\UuidV7(),
            'date' => 36,
            'admin_rights' => (string) new \Symfony\Component\Uid\UuidV7(),
            'banned_rights' => (string) new \Symfony\Component\Uid\UuidV7(),
            'default_banned_rights' => (string) new \Symfony\Component\Uid\UuidV7(),
            'participants_count' => 40,
            'stories_max_id' => (string) new \Symfony\Component\Uid\UuidV7(),
            'color' => (string) new \Symfony\Component\Uid\UuidV7(),
            'profile_color' => (string) new \Symfony\Component\Uid\UuidV7(),
            'emoji_status' => (string) new \Symfony\Component\Uid\UuidV7(),
            'level' => 45,
            'subscription_until_date' => 46,
            'bot_verification_icon' => 1047,
            'send_paid_messages_stars' => 1048,
            'linked_monoforum_id' => 1049,
        ];
    }
}
