<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBirthday;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotInfo;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotVerification;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBusinessAwayMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBusinessGreetingMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBusinessIntro;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBusinessLocation;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBusinessWorkHours;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatAdminRights;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatTheme;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDisallowedGiftsSettings;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDocument;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPeerNotifySettings;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPeerSettings;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPeerStories;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoto;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlProfileTab;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarRefProgram;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarsRating;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlTextWithEntities;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWallPaper;

/** Constructor model for userFull of UserFull (crc32 06cbe645). */
final class TlUserFullUserFull extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

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
        'pinned_msg_id' => 'int',
        'common_chats_count' => 'int',
        'folder_id' => 'int',
        'ttl_period' => 'int',
        'private_forward_name' => 'string',
        'personal_channel_id' => 'int',
        'personal_channel_message' => 'int',
        'stargifts_count' => 'int',
        'send_paid_messages_stars' => 'int',
        'stars_my_pending_rating_date' => 'int',
        'bot_manager_id' => 'int',
    ];

    public function settings(): BelongsTo
    {
        return $this->belongsTo(TlPeerSettings::class, 'settings');
    }
    public function personalPhoto(): BelongsTo
    {
        return $this->belongsTo(TlPhoto::class, 'personal_photo');
    }
    public function profilePhoto(): BelongsTo
    {
        return $this->belongsTo(TlPhoto::class, 'profile_photo');
    }
    public function fallbackPhoto(): BelongsTo
    {
        return $this->belongsTo(TlPhoto::class, 'fallback_photo');
    }
    public function notifySettings(): BelongsTo
    {
        return $this->belongsTo(TlPeerNotifySettings::class, 'notify_settings');
    }
    public function botInfo(): BelongsTo
    {
        return $this->belongsTo(TlBotInfo::class, 'bot_info');
    }
    public function theme(): BelongsTo
    {
        return $this->belongsTo(TlChatTheme::class, 'theme');
    }
    public function botGroupAdminRights(): BelongsTo
    {
        return $this->belongsTo(TlChatAdminRights::class, 'bot_group_admin_rights');
    }
    public function botBroadcastAdminRights(): BelongsTo
    {
        return $this->belongsTo(TlChatAdminRights::class, 'bot_broadcast_admin_rights');
    }
    public function wallpaper(): BelongsTo
    {
        return $this->belongsTo(TlWallPaper::class, 'wallpaper');
    }
    public function stories(): BelongsTo
    {
        return $this->belongsTo(TlPeerStories::class, 'stories');
    }
    public function businessWorkHours(): BelongsTo
    {
        return $this->belongsTo(TlBusinessWorkHours::class, 'business_work_hours');
    }
    public function businessLocation(): BelongsTo
    {
        return $this->belongsTo(TlBusinessLocation::class, 'business_location');
    }
    public function businessGreetingMessage(): BelongsTo
    {
        return $this->belongsTo(TlBusinessGreetingMessage::class, 'business_greeting_message');
    }
    public function businessAwayMessage(): BelongsTo
    {
        return $this->belongsTo(TlBusinessAwayMessage::class, 'business_away_message');
    }
    public function businessIntro(): BelongsTo
    {
        return $this->belongsTo(TlBusinessIntro::class, 'business_intro');
    }
    public function birthday(): BelongsTo
    {
        return $this->belongsTo(TlBirthday::class, 'birthday');
    }
    public function starrefProgram(): BelongsTo
    {
        return $this->belongsTo(TlStarRefProgram::class, 'starref_program');
    }
    public function botVerification(): BelongsTo
    {
        return $this->belongsTo(TlBotVerification::class, 'bot_verification');
    }
    public function disallowedGifts(): BelongsTo
    {
        return $this->belongsTo(TlDisallowedGiftsSettings::class, 'disallowed_gifts');
    }
    public function starsRating(): BelongsTo
    {
        return $this->belongsTo(TlStarsRating::class, 'stars_rating');
    }
    public function starsMyPendingRating(): BelongsTo
    {
        return $this->belongsTo(TlStarsRating::class, 'stars_my_pending_rating');
    }
    public function mainTab(): BelongsTo
    {
        return $this->belongsTo(TlProfileTab::class, 'main_tab');
    }
    public function savedMusic(): BelongsTo
    {
        return $this->belongsTo(TlDocument::class, 'saved_music');
    }
    public function note(): BelongsTo
    {
        return $this->belongsTo(TlTextWithEntities::class, 'note');
    }
}
