<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for channelAdminLogEventActionParticipantLeave of ChannelAdminLogEventAction (crc32 f89777f2). */
final class TlChannelAdminLogEventActionChannelAdminLogEventActionParticipantLeave extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_channel_admin_log_event_action_channel_adm_de4a28a2dca7';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
