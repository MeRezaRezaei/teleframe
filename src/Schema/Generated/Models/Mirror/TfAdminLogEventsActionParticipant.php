<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfAdminLogEventsActionParticipant extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_admin_log_events_action_participant';
    protected $primaryKey = 'parent_id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'muted' => 'boolean',
        'left' => 'boolean',
        'can_self_unmute' => 'boolean',
        'just_joined' => 'boolean',
        'versioned' => 'boolean',
        'min' => 'boolean',
        'muted_by_you' => 'boolean',
        'volume_by_admin' => 'boolean',
        'self' => 'boolean',
        'video_joined' => 'boolean',
        'peer_id' => 'integer',
        'date' => 'integer',
        'active_date' => 'integer',
        'source' => 'integer',
        'volume' => 'integer',
        'raise_hand_rating' => 'integer',
        'paid_stars_total' => 'integer',
    ];
}
