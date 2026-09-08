<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for userFull of UserFull (crc32 06cbe645). */
final class TlUserFullUserFull extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_user_full_user_full';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'blocked' => 'bool',
        'phone_calls_available' => 'bool',
        'phone_calls_private' => 'bool',
        'can_pin_message' => 'bool',
        'has_scheduled' => 'bool',
        'video_calls_available' => 'bool',
        'voice_messages_forbidden' => 'bool',
        'translations_disabled' => 'bool',
        'stories_pinned_available' => 'bool',
        'blocked_my_stories_from' => 'bool',
        'wallpaper_overridden' => 'bool',
        'contact_require_premium' => 'bool',
        'read_dates_private' => 'bool',
        'flags2' => 'int',
        'sponsored_enabled' => 'bool',
        'can_view_revenue' => 'bool',
        'bot_can_manage_emoji_status' => 'bool',
        'display_gifts_button' => 'bool',
        'noforwards_my_enabled' => 'bool',
        'noforwards_peer_enabled' => 'bool',
        'unofficial_security_risk' => 'bool',
        'tl_id' => 'int',
        'about' => 'string',
        'settings' => 'string',
        'personal_photo' => 'string',
        'profile_photo' => 'string',
        'fallback_photo' => 'string',
        'notify_settings' => 'string',
        'bot_info' => 'string',
        'pinned_msg_id' => 'int',
        'common_chats_count' => 'int',
        'folder_id' => 'int',
        'ttl_period' => 'int',
        'theme' => 'string',
        'private_forward_name' => 'string',
        'bot_group_admin_rights' => 'string',
        'bot_broadcast_admin_rights' => 'string',
        'wallpaper' => 'string',
        'stories' => 'string',
        'business_work_hours' => 'string',
        'business_location' => 'string',
        'business_greeting_message' => 'string',
        'business_away_message' => 'string',
        'business_intro' => 'string',
        'birthday' => 'string',
        'personal_channel_id' => 'int',
        'personal_channel_message' => 'int',
        'stargifts_count' => 'int',
        'starref_program' => 'string',
        'bot_verification' => 'string',
        'send_paid_messages_stars' => 'int',
        'disallowed_gifts' => 'string',
        'stars_rating' => 'string',
        'stars_my_pending_rating' => 'string',
        'stars_my_pending_rating_date' => 'int',
        'main_tab' => 'string',
        'saved_music' => 'string',
        'note' => 'string',
        'bot_manager_id' => 'int',
    ];
}
