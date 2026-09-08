<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for channelAdminLogEventsFilter of ChannelAdminLogEventsFilter (crc32 ea107ae4). */
final class TlChannelAdminLogEventsFilterChannelAdminLogEventsFilter extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_channel_admin_log_events_filter_channel_ad_2d07b3f742d8';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'join' => 'bool',
        'leave' => 'bool',
        'invite' => 'bool',
        'ban' => 'bool',
        'unban' => 'bool',
        'kick' => 'bool',
        'unkick' => 'bool',
        'promote' => 'bool',
        'demote' => 'bool',
        'info' => 'bool',
        'settings' => 'bool',
        'pinned' => 'bool',
        'edit' => 'bool',
        'delete' => 'bool',
        'group_call' => 'bool',
        'invites' => 'bool',
        'send' => 'bool',
        'forums' => 'bool',
        'sub_extend' => 'bool',
        'edit_rank' => 'bool',
    ];
}
