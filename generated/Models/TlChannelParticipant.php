<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionParticipantInvite;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionParticipantSubExtend;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionParticipantToggleAdmin;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionParticipantToggleBan;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelsChannelParticipantChannelParticipant;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateChannelParticipant;

/** Anchor model for TL type ChannelParticipant (spec §4.1). */
final class TlChannelParticipant extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_channel_participant_channel_participant';

    protected $guarded = [];

    public function newParticipant(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateChannelParticipant::class, 'new_participant');
    }
    public function newParticipantChannelAdminLogEventActionParticipantSubExtend(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionParticipantSubExtend::class, 'new_participant');
    }
    public function newParticipantChannelAdminLogEventActionParticipantToggleAdmin(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionParticipantToggleAdmin::class, 'new_participant');
    }
    public function newParticipantChannelAdminLogEventActionParticipantToggleBan(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionParticipantToggleBan::class, 'new_participant');
    }
    public function participant(): HasMany
    {
        return $this->hasMany(TlChannelsChannelParticipantChannelParticipant::class, 'participant');
    }
    public function participantChannelAdminLogEventActionParticipantInvite(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionParticipantInvite::class, 'participant');
    }
    public function prevParticipant(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateChannelParticipant::class, 'prev_participant');
    }
    public function prevParticipantChannelAdminLogEventActionParticipantSubExtend(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionParticipantSubExtend::class, 'prev_participant');
    }
    public function prevParticipantChannelAdminLogEventActionParticipantToggleAdmin(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionParticipantToggleAdmin::class, 'prev_participant');
    }
    public function prevParticipantChannelAdminLogEventActionParticipantToggleBan(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionParticipantToggleBan::class, 'prev_participant');
    }
}
