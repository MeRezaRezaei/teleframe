<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for channelAdminLogEventActionParticipantJoin of ChannelAdminLogEventAction (crc32 183040d3). */
final class TlChannelAdminLogEventActionChannelAdminLogEventActionParticipantJoin extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_channel_admin_log_event_action_channel_adm_def61dc1b5d2';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
