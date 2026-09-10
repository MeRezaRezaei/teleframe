<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotVerification;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelLocation;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatFullChannelFullBot_info;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatFullChannelFullPending_suggestions;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatFullChannelFullRecent_requesters;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatReactions;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlExportedChatInvite;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputGroupCall;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPeerNotifySettings;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPeerStories;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhoto;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlProfileTab;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStickerSet;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWallPaper;

/** Constructor model for channelFull of ChatFull (crc32 a04e8d3a). */
final class TlChatFullChannelFull extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;
    use PeerResolution;

    protected $table = 'tl_chat_full_channel_full';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'can_view_participants' => 'bool',
        'can_set_username' => 'bool',
        'can_set_stickers' => 'bool',
        'hidden_prehistory' => 'bool',
        'can_set_location' => 'bool',
        'has_scheduled' => 'bool',
        'can_view_stats' => 'bool',
        'blocked' => 'bool',
        'flags2' => 'int',
        'can_delete_channel' => 'bool',
        'antispam' => 'bool',
        'participants_hidden' => 'bool',
        'translations_disabled' => 'bool',
        'stories_pinned_available' => 'bool',
        'view_forum_as_messages' => 'bool',
        'restricted_sponsored' => 'bool',
        'can_view_revenue' => 'bool',
        'paid_media_allowed' => 'bool',
        'can_view_stars_revenue' => 'bool',
        'paid_reactions_available' => 'bool',
        'stargifts_available' => 'bool',
        'paid_messages_available' => 'bool',
        'tl_id' => 'int',
        'about' => 'string',
        'participants_count' => 'int',
        'admins_count' => 'int',
        'kicked_count' => 'int',
        'banned_count' => 'int',
        'online_count' => 'int',
        'read_inbox_max_id' => 'int',
        'read_outbox_max_id' => 'int',
        'unread_count' => 'int',
        'migrated_from_chat_id' => 'int',
        'migrated_from_max_id' => 'int',
        'pinned_msg_id' => 'int',
        'available_min_id' => 'int',
        'folder_id' => 'int',
        'linked_chat_id' => 'int',
        'slowmode_seconds' => 'int',
        'slowmode_next_send_date' => 'int',
        'stats_dc' => 'int',
        'pts' => 'int',
        'ttl_period' => 'int',
        'theme_emoticon' => 'string',
        'requests_pending' => 'int',
        'reactions_limit' => 'int',
        'boosts_applied' => 'int',
        'boosts_unrestrict' => 'int',
        'stargifts_count' => 'int',
        'send_paid_messages_stars' => 'int',
        'guard_bot_id' => 'int',
    ];

    public function botInfo(): HasMany
    {
        return $this->tlChild(TlChatFullChannelFullBot_info::class);
    }
    public function pendingSuggestions(): HasMany
    {
        return $this->tlChild(TlChatFullChannelFullPending_suggestions::class);
    }
    public function recentRequesters(): HasMany
    {
        return $this->tlChild(TlChatFullChannelFullRecent_requesters::class);
    }

    public function chatPhoto(): BelongsTo
    {
        return $this->belongsTo(TlPhoto::class, 'chat_photo');
    }
    public function notifySettings(): BelongsTo
    {
        return $this->belongsTo(TlPeerNotifySettings::class, 'notify_settings');
    }
    public function exportedInvite(): BelongsTo
    {
        return $this->belongsTo(TlExportedChatInvite::class, 'exported_invite');
    }
    public function stickerset(): BelongsTo
    {
        return $this->belongsTo(TlStickerSet::class, 'stickerset');
    }
    public function location(): BelongsTo
    {
        return $this->belongsTo(TlChannelLocation::class, 'location');
    }
    public function call(): BelongsTo
    {
        return $this->belongsTo(TlInputGroupCall::class, 'call');
    }
    public function availableReactions(): BelongsTo
    {
        return $this->belongsTo(TlChatReactions::class, 'available_reactions');
    }
    public function stories(): BelongsTo
    {
        return $this->belongsTo(TlPeerStories::class, 'stories');
    }
    public function wallpaper(): BelongsTo
    {
        return $this->belongsTo(TlWallPaper::class, 'wallpaper');
    }
    public function emojiset(): BelongsTo
    {
        return $this->belongsTo(TlStickerSet::class, 'emojiset');
    }
    public function botVerification(): BelongsTo
    {
        return $this->belongsTo(TlBotVerification::class, 'bot_verification');
    }
    public function mainTab(): BelongsTo
    {
        return $this->belongsTo(TlProfileTab::class, 'main_tab');
    }
}
