<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionParticipantMute;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionParticipantUnmute;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionParticipantVolume;

/** Anchor model for TL type GroupCallParticipant (spec §4.1). */
final class TlGroupCallParticipant extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_group_call_participant_group_call_participant';

    protected $guarded = [];

    public function participant(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionParticipantMute::class, 'participant');
    }
    public function participantChannelAdminLogEventActionParticipantUnmute(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionParticipantUnmute::class, 'participant');
    }
    public function participantChannelAdminLogEventActionParticipantVolume(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionParticipantVolume::class, 'participant');
    }
}
