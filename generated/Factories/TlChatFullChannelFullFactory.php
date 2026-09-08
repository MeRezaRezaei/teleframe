<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlChatFullChannelFull (channelFull). */
final class TlChatFullChannelFullFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatFullChannelFull> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatFullChannelFull::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'can_view_participants' => true,
            'can_set_username' => true,
            'can_set_stickers' => true,
            'hidden_prehistory' => true,
            'can_set_location' => true,
            'has_scheduled' => true,
            'can_view_stats' => true,
            'blocked' => true,
            'flags2' => 10,
            'can_delete_channel' => true,
            'antispam' => true,
            'participants_hidden' => true,
            'translations_disabled' => true,
            'stories_pinned_available' => true,
            'view_forum_as_messages' => true,
            'restricted_sponsored' => true,
            'can_view_revenue' => true,
            'paid_media_allowed' => true,
            'can_view_stars_revenue' => true,
            'paid_reactions_available' => true,
            'stargifts_available' => true,
            'paid_messages_available' => true,
            'tl_id' => 1024,
            'about' => 'about-25',
            'participants_count' => 26,
            'admins_count' => 27,
            'kicked_count' => 28,
            'banned_count' => 29,
            'online_count' => 30,
            'read_inbox_max_id' => 31,
            'read_outbox_max_id' => 32,
            'unread_count' => 33,
            'chat_photo' => (string) new \Symfony\Component\Uid\UuidV7(),
            'notify_settings' => (string) new \Symfony\Component\Uid\UuidV7(),
            'exported_invite' => (string) new \Symfony\Component\Uid\UuidV7(),
            'migrated_from_chat_id' => 1037,
            'migrated_from_max_id' => 38,
            'pinned_msg_id' => 39,
            'stickerset' => (string) new \Symfony\Component\Uid\UuidV7(),
            'available_min_id' => 41,
            'folder_id' => 42,
            'linked_chat_id' => 1043,
            'location' => (string) new \Symfony\Component\Uid\UuidV7(),
            'slowmode_seconds' => 45,
            'slowmode_next_send_date' => 46,
            'stats_dc' => 47,
            'pts' => 48,
            'call' => (string) new \Symfony\Component\Uid\UuidV7(),
            'ttl_period' => 50,
            'groupcall_default_join_as' => (string) new \Symfony\Component\Uid\UuidV7(),
            'theme_emoticon' => 'theme_emoticon-52',
            'requests_pending' => 53,
            'default_send_as' => (string) new \Symfony\Component\Uid\UuidV7(),
            'available_reactions' => (string) new \Symfony\Component\Uid\UuidV7(),
            'reactions_limit' => 56,
            'stories' => (string) new \Symfony\Component\Uid\UuidV7(),
            'wallpaper' => (string) new \Symfony\Component\Uid\UuidV7(),
            'boosts_applied' => 59,
            'boosts_unrestrict' => 60,
            'emojiset' => (string) new \Symfony\Component\Uid\UuidV7(),
            'bot_verification' => (string) new \Symfony\Component\Uid\UuidV7(),
            'stargifts_count' => 63,
            'send_paid_messages_stars' => 1064,
            'main_tab' => (string) new \Symfony\Component\Uid\UuidV7(),
            'guard_bot_id' => 1066,
        ];
    }
}
