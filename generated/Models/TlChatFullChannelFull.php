<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatFullChannelFullBot_info;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatFullChannelFullPending_suggestions;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatFullChannelFullRecent_requesters;

/** Constructor model for channelFull of ChatFull (crc32 a04e8d3a). */
final class TlChatFullChannelFull extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

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
        'chat_photo' => 'string',
        'notify_settings' => 'string',
        'exported_invite' => 'string',
        'migrated_from_chat_id' => 'int',
        'migrated_from_max_id' => 'int',
        'pinned_msg_id' => 'int',
        'stickerset' => 'string',
        'available_min_id' => 'int',
        'folder_id' => 'int',
        'linked_chat_id' => 'int',
        'location' => 'string',
        'slowmode_seconds' => 'int',
        'slowmode_next_send_date' => 'int',
        'stats_dc' => 'int',
        'pts' => 'int',
        'call' => 'string',
        'ttl_period' => 'int',
        'groupcall_default_join_as' => 'string',
        'theme_emoticon' => 'string',
        'requests_pending' => 'int',
        'default_send_as' => 'string',
        'available_reactions' => 'string',
        'reactions_limit' => 'int',
        'stories' => 'string',
        'wallpaper' => 'string',
        'boosts_applied' => 'int',
        'boosts_unrestrict' => 'int',
        'emojiset' => 'string',
        'bot_verification' => 'string',
        'stargifts_count' => 'int',
        'send_paid_messages_stars' => 'int',
        'main_tab' => 'string',
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
}
