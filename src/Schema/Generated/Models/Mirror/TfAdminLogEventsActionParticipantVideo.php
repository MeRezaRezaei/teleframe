<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfAdminLogEventsActionParticipantVideo extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_admin_log_events_action_participant_video';
    protected $primaryKey = 'parent_id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'paused' => 'boolean',
        'audio_source' => 'integer',
    ];
}
