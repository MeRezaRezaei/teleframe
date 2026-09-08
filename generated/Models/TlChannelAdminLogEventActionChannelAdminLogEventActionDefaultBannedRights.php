<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for channelAdminLogEventActionDefaultBannedRights of ChannelAdminLogEventAction (crc32 2df5fc0a). */
final class TlChannelAdminLogEventActionChannelAdminLogEventActionDefaultBannedRights extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_channel_admin_log_event_action_channel_adm_d30d8ce61800';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'prev_banned_rights' => 'string',
        'new_banned_rights' => 'string',
    ];
}
