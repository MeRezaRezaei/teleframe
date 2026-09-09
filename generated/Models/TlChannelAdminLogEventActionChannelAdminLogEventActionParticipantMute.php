<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlGroupCallParticipant;

/** Constructor model for channelAdminLogEventActionParticipantMute of ChannelAdminLogEventAction (crc32 f92424d2). */
final class TlChannelAdminLogEventActionChannelAdminLogEventActionParticipantMute extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_channel_admin_log_event_action_channel_adm_5db283c442db';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function participant(): BelongsTo
    {
        return $this->belongsTo(TlGroupCallParticipant::class, 'participant');
    }
}
