<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfDialogsNotifySetting extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_dialogs_notify_settings';
    protected $primaryKey = 'parent_id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'peer_id' => 'integer',
        'show_previews' => 'boolean',
        'silent' => 'boolean',
        'mute_until' => 'integer',
        'stories_muted' => 'boolean',
        'stories_hide_sender' => 'boolean',
    ];
}
