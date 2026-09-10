<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** Factory for TlUserUser (user). */
final class TlUserUserFactory extends Factory
{
    /** @var class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUserUser> */
    protected $model = \MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUserUser::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'flags' => 1,
            'self' => true,
            'contact' => true,
            'mutual_contact' => true,
            'deleted' => true,
            'bot' => true,
            'bot_chat_history' => true,
            'bot_nochats' => true,
            'verified' => true,
            'restricted' => true,
            'min' => true,
            'bot_inline_geo' => true,
            'support' => true,
            'scam' => true,
            'apply_min_photo' => true,
            'fake' => true,
            'bot_attach_menu' => true,
            'premium' => true,
            'attach_menu_enabled' => true,
            'flags2' => 20,
            'bot_can_edit' => true,
            'close_friend' => true,
            'stories_hidden' => true,
            'stories_unavailable' => true,
            'contact_require_premium' => true,
            'bot_business' => true,
            'bot_has_main_app' => true,
            'bot_forum_view' => true,
            'bot_forum_can_manage_topics' => true,
            'bot_can_manage_bots' => true,
            'bot_guestchat' => true,
            'bot_guard' => true,
            'tl_id' => 1033,
            'access_hash' => 1034,
            'first_name' => 'first_name-35',
            'last_name' => 'last_name-36',
            'username' => 'username-37',
            'phone' => 'phone-38',
            'photo' => 1039,
            'status' => 1040,
            'bot_info_version' => 41,
            'bot_inline_placeholder' => 'bot_inline_placeholder-42',
            'lang_code' => 'lang_code-43',
            'emoji_status' => 1044,
            'stories_max_id' => 1045,
            'color' => 1046,
            'profile_color' => 1047,
            'bot_active_users' => 48,
            'bot_verification_icon' => 1049,
            'send_paid_messages_stars' => 1050,
        ];
    }
}
