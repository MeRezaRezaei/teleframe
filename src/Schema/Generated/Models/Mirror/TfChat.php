<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

final class TfChat extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_chats';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'participants_count' => 'integer',
        'date' => 'integer',
        'version' => 'integer',
        'creator' => 'boolean',
        'left' => 'boolean',
        'deactivated' => 'boolean',
        'call_active' => 'boolean',
        'call_not_empty' => 'boolean',
        'noforwards' => 'boolean',
    ];
}
