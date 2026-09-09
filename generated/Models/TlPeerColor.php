<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionChangePeerColor;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionChangeProfilePeerColor;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatChannel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSponsoredMessageSponsoredMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftStarGiftUnique;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUserUser;

/** Anchor model for TL type PeerColor (spec §4.1). */
final class TlPeerColor extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_peer_color';

    protected $guarded = [];

    public function color(): HasMany
    {
        return $this->hasMany(TlUserUser::class, 'color');
    }
    public function colorChannel(): HasMany
    {
        return $this->hasMany(TlChatChannel::class, 'color');
    }
    public function colorSponsoredMessage(): HasMany
    {
        return $this->hasMany(TlSponsoredMessageSponsoredMessage::class, 'color');
    }
    public function newValue(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionChangePeerColor::class, 'new_value');
    }
    public function newValueChannelAdminLogEventActionChangeProfilePeerColor(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionChangeProfilePeerColor::class, 'new_value');
    }
    public function peerColor(): HasMany
    {
        return $this->hasMany(TlStarGiftStarGiftUnique::class, 'peer_color');
    }
    public function prevValue(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionChangePeerColor::class, 'prev_value');
    }
    public function prevValueChannelAdminLogEventActionChangeProfilePeerColor(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionChangeProfilePeerColor::class, 'prev_value');
    }
    public function profileColor(): HasMany
    {
        return $this->hasMany(TlUserUser::class, 'profile_color');
    }
    public function profileColorChannel(): HasMany
    {
        return $this->hasMany(TlChatChannel::class, 'profile_color');
    }
}
