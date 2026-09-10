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
            'settings' => 1025,
            'personal_photo' => 1026,
            'profile_photo' => 1027,
            'fallback_photo' => 1028,
            'notify_settings' => 1029,
            'bot_info' => 1030,
            'pinned_msg_id' => 31,
            'common_chats_count' => 32,
            'folder_id' => 33,
            'ttl_period' => 34,
            'theme' => 1035,
            'private_forward_name' => 'private_forward_name-36',
            'bot_group_admin_rights' => 1037,
            'bot_broadcast_admin_rights' => 1038,
            'wallpaper' => 1039,
            'stories' => 1040,
            'business_work_hours' => 1041,
            'business_location' => 1042,
            'business_greeting_message' => 1043,
            'business_away_message' => 1044,
            'business_intro' => 1045,
            'birthday' => 1046,
            'personal_channel_id' => 1047,
            'personal_channel_message' => 48,
            'stargifts_count' => 49,
            'starref_program' => 1050,
            'bot_verification' => 1051,
            'send_paid_messages_stars' => 1052,
            'disallowed_gifts' => 1053,
            'stars_rating' => 1054,
            'stars_my_pending_rating' => 1055,
            'stars_my_pending_rating_date' => 56,
            'main_tab' => 1057,
            'saved_music' => 1058,
            'note' => 1059,
            'bot_manager_id' => 1060,
        ];
    }
}
