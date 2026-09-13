<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfAdminLogEventsActionPrevInvite extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_admin_log_events_action_prev_invite';
    protected $primaryKey = 'parent_id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'revoked' => 'boolean',
        'permanent' => 'boolean',
        'request_needed' => 'boolean',
        'admin_id' => 'integer',
        'date' => 'integer',
        'start_date' => 'integer',
        'expire_date' => 'integer',
        'usage_limit' => 'integer',
        'usage' => 'integer',
        'requested' => 'integer',
        'subscription_expired' => 'integer',
    ];
}
