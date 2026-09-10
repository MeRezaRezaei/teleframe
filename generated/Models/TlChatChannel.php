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
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatAdminRights;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatBannedRights;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatChannelRestriction_reason;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatChannelUsernames;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatPhoto;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlEmojiStatus;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPeerColor;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRecentStory;

/** Constructor model for channel of Chat (crc32 1c32b11c). */
final class TlChatChannel extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_chat_channel';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'creator' => 'bool',
        'left' => 'bool',
        'broadcast' => 'bool',
        'verified' => 'bool',
        'megagroup' => 'bool',
        'restricted' => 'bool',
        'signatures' => 'bool',
        'min' => 'bool',
        'scam' => 'bool',
        'has_link' => 'bool',
        'has_geo' => 'bool',
        'slowmode_enabled' => 'bool',
        'call_active' => 'bool',
        'call_not_empty' => 'bool',
        'fake' => 'bool',
        'gigagroup' => 'bool',
        'noforwards' => 'bool',
        'join_to_send' => 'bool',
        'join_request' => 'bool',
        'forum' => 'bool',
        'flags2' => 'int',
        'stories_hidden' => 'bool',
        'stories_hidden_min' => 'bool',
        'stories_unavailable' => 'bool',
        'signature_profiles' => 'bool',
        'autotranslation' => 'bool',
        'broadcast_messages_allowed' => 'bool',
        'monoforum' => 'bool',
        'forum_tabs' => 'bool',
        'tl_id' => 'int',
        'access_hash' => 'int',
        'title' => 'string',
        'username' => 'string',
        'date' => 'int',
        'participants_count' => 'int',
        'level' => 'int',
        'subscription_until_date' => 'int',
        'bot_verification_icon' => 'int',
        'send_paid_messages_stars' => 'int',
        'linked_monoforum_id' => 'int',
    ];

    public function restrictionReason(): HasMany
    {
        return $this->tlChild(TlChatChannelRestriction_reason::class);
    }
    public function usernames(): HasMany
    {
        return $this->tlChild(TlChatChannelUsernames::class);
    }

    public function photo(): BelongsTo
    {
        return $this->belongsTo(TlChatPhoto::class, 'photo');
    }
    public function adminRights(): BelongsTo
    {
        return $this->belongsTo(TlChatAdminRights::class, 'admin_rights');
    }
    public function bannedRights(): BelongsTo
    {
        return $this->belongsTo(TlChatBannedRights::class, 'banned_rights');
    }
    public function defaultBannedRights(): BelongsTo
    {
        return $this->belongsTo(TlChatBannedRights::class, 'default_banned_rights');
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
    public function emojiStatus(): BelongsTo
    {
        return $this->belongsTo(TlEmojiStatus::class, 'emoji_status');
    }
}
