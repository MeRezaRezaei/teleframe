<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for channelAdminLogEventActionChangeWallpaper of ChannelAdminLogEventAction (crc32 31bb5d52). */
final class TlChannelAdminLogEventActionChannelAdminLogEventActionChangeWallpaper extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_channel_admin_log_event_action_channel_adm_573029f966f9';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'prev_value' => 'string',
        'new_value' => 'string',
    ];
}
