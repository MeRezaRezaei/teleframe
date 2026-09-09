<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionDefaultBannedRights;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelParticipantChannelParticipantBanned;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatChannel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatChat;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateChatDefaultBannedRights;

/** Anchor model for TL type ChatBannedRights (spec §4.1). */
final class TlChatBannedRights extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_chat_banned_rights';

    protected $guarded = [];

    public function bannedRights(): HasMany
    {
        return $this->hasMany(TlChatChannel::class, 'banned_rights');
    }
    public function bannedRightsChannelParticipantBanned(): HasMany
    {
        return $this->hasMany(TlChannelParticipantChannelParticipantBanned::class, 'banned_rights');
    }
    public function defaultBannedRights(): HasMany
    {
        return $this->hasMany(TlChatChat::class, 'default_banned_rights');
    }
    public function defaultBannedRightsChannel(): HasMany
    {
        return $this->hasMany(TlChatChannel::class, 'default_banned_rights');
    }
    public function defaultBannedRightsUpdateChatDefaultBannedRights(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateChatDefaultBannedRights::class, 'default_banned_rights');
    }
    public function newBannedRights(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionDefaultBannedRights::class, 'new_banned_rights');
    }
    public function prevBannedRights(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionDefaultBannedRights::class, 'prev_banned_rights');
    }
}
