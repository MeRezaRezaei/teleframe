<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatChannelRestriction_reason;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatChannelUsernames;

/** Constructor model for channel of Chat (crc32 1c32b11c). */
final class TlChatChannel extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

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
        'photo' => 'string',
        'date' => 'int',
        'admin_rights' => 'string',
        'banned_rights' => 'string',
        'default_banned_rights' => 'string',
        'participants_count' => 'int',
        'stories_max_id' => 'string',
        'color' => 'string',
        'profile_color' => 'string',
        'emoji_status' => 'string',
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
}
