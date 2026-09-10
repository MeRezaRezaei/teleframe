<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for channelAdminLogEventActionToggleSlowMode of ChannelAdminLogEventAction (crc32 53909779). */
final class TlChannelAdminLogEventActionChannelAdminLogEventActionToggleSlowMode extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_channel_admin_log_event_action_channel_adm_5b0d4c7f48ce';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'prev_value' => 'int',
        'new_value' => 'int',
    ];
}
