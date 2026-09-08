<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for channelAdminLogEventActionChangePhoto of ChannelAdminLogEventAction (crc32 434bd2af). */
final class TlChannelAdminLogEventActionChannelAdminLogEventActionChangePhoto extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_channel_admin_log_event_action_channel_adm_1d1939e936ae';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'prev_photo' => 'string',
        'new_photo' => 'string',
    ];
}
