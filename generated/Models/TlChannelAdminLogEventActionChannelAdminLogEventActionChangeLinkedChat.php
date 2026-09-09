<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for channelAdminLogEventActionChangeLinkedChat of ChannelAdminLogEventAction (crc32 050c7ac8). */
final class TlChannelAdminLogEventActionChannelAdminLogEventActionChangeLinkedChat extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_channel_admin_log_event_action_channel_adm_30be48f8e9ac';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'prev_value' => 'int',
        'new_value' => 'int',
    ];
}
