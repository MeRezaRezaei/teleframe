<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfMessagesFwdFrom extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_messages_fwd_from';
    protected $primaryKey = 'parent_id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'imported' => 'boolean',
        'saved_out' => 'boolean',
        'from_id_id' => 'integer',
        'date' => 'integer',
        'channel_post' => 'integer',
        'saved_from_peer_id' => 'integer',
        'saved_from_msg_id' => 'integer',
        'saved_from_id_id' => 'integer',
        'saved_date' => 'integer',
    ];
}
