<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for channelAdminLogEventActionToggleNoForwards of ChannelAdminLogEventAction (crc32 cb2ac766). */
final class TlChannelAdminLogEventActionChannelAdminLogEventActionToggleNoForwards extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_channel_admin_log_event_action_channel_adm_e9b139f92b2c';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'new_value' => 'string',
    ];
}
