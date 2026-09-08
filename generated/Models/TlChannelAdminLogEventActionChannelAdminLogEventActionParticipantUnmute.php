<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for channelAdminLogEventActionParticipantUnmute of ChannelAdminLogEventAction (crc32 e64429c0). */
final class TlChannelAdminLogEventActionChannelAdminLogEventActionParticipantUnmute extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_channel_admin_log_event_action_channel_adm_f449405b5cbf';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'participant' => 'string',
    ];
}
