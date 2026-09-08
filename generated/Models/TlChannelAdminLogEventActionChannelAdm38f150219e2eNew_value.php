<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param new_value (table tl_channel_admin_log_event_action_channel_adm_54649cabd20d). */
final class TlChannelAdminLogEventActionChannelAdm38f150219e2eNew_value extends TlAnchorModel
{
    protected $table = 'tl_channel_admin_log_event_action_channel_adm_54649cabd20d';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'value' => 'string',
    ];
}
