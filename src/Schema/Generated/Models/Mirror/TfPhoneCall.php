<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

final class TfPhoneCall extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_phone_calls';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'access_hash' => 'integer',
        'date' => 'integer',
        'admin_id' => 'integer',
        'participant_id' => 'integer',
        'video' => 'boolean',
    ];
}
