<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelParticipant;

/** Constructor model for channelAdminLogEventActionParticipantToggleBan of ChannelAdminLogEventAction (crc32 e6d83d7e). */
final class TlChannelAdminLogEventActionChannelAdminLogEventActionParticipantToggleBan extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_channel_admin_log_event_action_channel_adm_b8859bb8b9a0';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function prevParticipant(): BelongsTo
    {
        return $this->belongsTo(TlChannelParticipant::class, 'prev_participant');
    }
    public function newParticipant(): BelongsTo
    {
        return $this->belongsTo(TlChannelParticipant::class, 'new_participant');
    }
}
