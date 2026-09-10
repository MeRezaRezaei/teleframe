<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelParticipantChannelParticipantAdmin;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelParticipantChannelParticipantCreator;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatChannel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatChat;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRequestPeerTypeRequestPeerTypeBroadcast;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRequestPeerTypeRequestPeerTypeChat;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUserFullUserFull;

/** Anchor model for TL type ChatAdminRights (spec §4.1). */
final class TlChatAdminRights extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_chat_admin_rights_chat_admin_rights';

    protected $guarded = [];

    public function adminRights(): HasMany
    {
        return $this->hasMany(TlChatChat::class, 'admin_rights');
    }
    public function adminRightsChannel(): HasMany
    {
        return $this->hasMany(TlChatChannel::class, 'admin_rights');
    }
    public function adminRightsChannelParticipantAdmin(): HasMany
    {
        return $this->hasMany(TlChannelParticipantChannelParticipantAdmin::class, 'admin_rights');
    }
    public function adminRightsChannelParticipantCreator(): HasMany
    {
        return $this->hasMany(TlChannelParticipantChannelParticipantCreator::class, 'admin_rights');
    }
    public function botAdminRights(): HasMany
    {
        return $this->hasMany(TlRequestPeerTypeRequestPeerTypeChat::class, 'bot_admin_rights');
    }
    public function botAdminRightsRequestPeerTypeBroadcast(): HasMany
    {
        return $this->hasMany(TlRequestPeerTypeRequestPeerTypeBroadcast::class, 'bot_admin_rights');
    }
    public function botBroadcastAdminRights(): HasMany
    {
        return $this->hasMany(TlUserFullUserFull::class, 'bot_broadcast_admin_rights');
    }
    public function botGroupAdminRights(): HasMany
    {
        return $this->hasMany(TlUserFullUserFull::class, 'bot_group_admin_rights');
    }
    public function userAdminRights(): HasMany
    {
        return $this->hasMany(TlRequestPeerTypeRequestPeerTypeChat::class, 'user_admin_rights');
    }
    public function userAdminRightsRequestPeerTypeBroadcast(): HasMany
    {
        return $this->hasMany(TlRequestPeerTypeRequestPeerTypeBroadcast::class, 'user_admin_rights');
    }
}
