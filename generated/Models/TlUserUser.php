<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlEmojiStatus;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPeerColor;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRecentStory;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUserProfilePhoto;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUserStatus;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUserUserRestriction_reason;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUserUserUsernames;

/** Constructor model for user of User (crc32 31774388). */
final class TlUserUser extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_user_user';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'self' => 'bool',
        'contact' => 'bool',
        'mutual_contact' => 'bool',
        'deleted' => 'bool',
        'bot' => 'bool',
        'bot_chat_history' => 'bool',
        'bot_nochats' => 'bool',
        'verified' => 'bool',
        'restricted' => 'bool',
        'min' => 'bool',
        'bot_inline_geo' => 'bool',
        'support' => 'bool',
        'scam' => 'bool',
        'apply_min_photo' => 'bool',
        'fake' => 'bool',
        'bot_attach_menu' => 'bool',
        'premium' => 'bool',
        'attach_menu_enabled' => 'bool',
        'flags2' => 'int',
        'bot_can_edit' => 'bool',
        'close_friend' => 'bool',
        'stories_hidden' => 'bool',
        'stories_unavailable' => 'bool',
        'contact_require_premium' => 'bool',
        'bot_business' => 'bool',
        'bot_has_main_app' => 'bool',
        'bot_forum_view' => 'bool',
        'bot_forum_can_manage_topics' => 'bool',
        'bot_can_manage_bots' => 'bool',
        'bot_guestchat' => 'bool',
        'bot_guard' => 'bool',
        'tl_id' => 'int',
        'access_hash' => 'int',
        'first_name' => 'string',
        'last_name' => 'string',
        'username' => 'string',
        'phone' => 'string',
        'bot_info_version' => 'int',
        'bot_inline_placeholder' => 'string',
        'lang_code' => 'string',
        'bot_active_users' => 'int',
        'bot_verification_icon' => 'int',
        'send_paid_messages_stars' => 'int',
    ];

    public function restrictionReason(): HasMany
    {
        return $this->tlChild(TlUserUserRestriction_reason::class);
    }
    public function usernames(): HasMany
    {
        return $this->tlChild(TlUserUserUsernames::class);
    }

    public function photo(): BelongsTo
    {
        return $this->belongsTo(TlUserProfilePhoto::class, 'photo');
    }
    public function status(): BelongsTo
    {
        return $this->belongsTo(TlUserStatus::class, 'status');
    }
    public function emojiStatus(): BelongsTo
    {
        return $this->belongsTo(TlEmojiStatus::class, 'emoji_status');
    }
    public function storiesMaxId(): BelongsTo
    {
        return $this->belongsTo(TlRecentStory::class, 'stories_max_id');
    }
    public function color(): BelongsTo
    {
        return $this->belongsTo(TlPeerColor::class, 'color');
    }
    public function profileColor(): BelongsTo
    {
        return $this->belongsTo(TlPeerColor::class, 'profile_color');
    }
}
