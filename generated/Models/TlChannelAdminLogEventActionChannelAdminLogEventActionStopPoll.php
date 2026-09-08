<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for channelAdminLogEventActionStopPoll of ChannelAdminLogEventAction (crc32 8f079643). */
final class TlChannelAdminLogEventActionChannelAdminLogEventActionStopPoll extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_channel_admin_log_event_action_channel_adm_af87ed20f3e9';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'message' => 'string',
    ];
}
