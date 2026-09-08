<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for channelAdminLogEventActionEditMessage of ChannelAdminLogEventAction (crc32 709b2405). */
final class TlChannelAdminLogEventActionChannelAdminLogEventActionEditMessage extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_channel_admin_log_event_action_channel_adm_c24d817da4e3';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'prev_message' => 'string',
        'new_message' => 'string',
    ];
}
