<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUserFullUserFull (userFull). */
final class TlUserFullUserFullFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUserFullUserFull> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUserFullUserFull::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'blocked' => true,
            'phone_calls_available' => true,
            'phone_calls_private' => true,
            'can_pin_message' => true,
            'has_scheduled' => true,
            'video_calls_available' => true,
            'voice_messages_forbidden' => true,
            'translations_disabled' => true,
            'stories_pinned_available' => true,
            'blocked_my_stories_from' => true,
            'wallpaper_overridden' => true,
            'contact_require_premium' => true,
            'read_dates_private' => true,
            'flags2' => 15,
            'sponsored_enabled' => true,
            'can_view_revenue' => true,
            'bot_can_manage_emoji_status' => true,
            'display_gifts_button' => true,
            'noforwards_my_enabled' => true,
            'noforwards_peer_enabled' => true,
            'unofficial_security_risk' => true,
            'tl_id' => 1023,
            'about' => 'about-24',
            'settings' => (string) new \Symfony\Component\Uid\UuidV7(),
            'personal_photo' => (string) new \Symfony\Component\Uid\UuidV7(),
            'profile_photo' => (string) new \Symfony\Component\Uid\UuidV7(),
            'fallback_photo' => (string) new \Symfony\Component\Uid\UuidV7(),
            'notify_settings' => (string) new \Symfony\Component\Uid\UuidV7(),
            'bot_info' => (string) new \Symfony\Component\Uid\UuidV7(),
            'pinned_msg_id' => 31,
            'common_chats_count' => 32,
            'folder_id' => 33,
            'ttl_period' => 34,
            'theme' => (string) new \Symfony\Component\Uid\UuidV7(),
            'private_forward_name' => 'private_forward_name-36',
            'bot_group_admin_rights' => (string) new \Symfony\Component\Uid\UuidV7(),
            'bot_broadcast_admin_rights' => (string) new \Symfony\Component\Uid\UuidV7(),
            'wallpaper' => (string) new \Symfony\Component\Uid\UuidV7(),
            'stories' => (string) new \Symfony\Component\Uid\UuidV7(),
            'business_work_hours' => (string) new \Symfony\Component\Uid\UuidV7(),
            'business_location' => (string) new \Symfony\Component\Uid\UuidV7(),
            'business_greeting_message' => (string) new \Symfony\Component\Uid\UuidV7(),
            'business_away_message' => (string) new \Symfony\Component\Uid\UuidV7(),
            'business_intro' => (string) new \Symfony\Component\Uid\UuidV7(),
            'birthday' => (string) new \Symfony\Component\Uid\UuidV7(),
            'personal_channel_id' => 1047,
            'personal_channel_message' => 48,
            'stargifts_count' => 49,
            'starref_program' => (string) new \Symfony\Component\Uid\UuidV7(),
            'bot_verification' => (string) new \Symfony\Component\Uid\UuidV7(),
            'send_paid_messages_stars' => 1052,
            'disallowed_gifts' => (string) new \Symfony\Component\Uid\UuidV7(),
            'stars_rating' => (string) new \Symfony\Component\Uid\UuidV7(),
            'stars_my_pending_rating' => (string) new \Symfony\Component\Uid\UuidV7(),
            'stars_my_pending_rating_date' => 56,
            'main_tab' => (string) new \Symfony\Component\Uid\UuidV7(),
            'saved_music' => (string) new \Symfony\Component\Uid\UuidV7(),
            'note' => (string) new \Symfony\Component\Uid\UuidV7(),
            'bot_manager_id' => 1060,
        ];
    }
}
