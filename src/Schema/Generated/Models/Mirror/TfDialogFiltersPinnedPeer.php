<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfDialogFiltersPinnedPeer extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_dialog_filters_pinned_peers';
    protected $primaryKey = 'parent_id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'chat_id' => 'integer',
        'user_id' => 'integer',
        'access_hash' => 'integer',
        'channel_id' => 'integer',
        'msg_id' => 'integer',
    ];
}
