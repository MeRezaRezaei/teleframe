<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfPhoneCallsProtocol extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_phone_calls_protocol';
    protected $primaryKey = 'parent_id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'udp_p2p' => 'boolean',
        'udp_reflector' => 'boolean',
        'min_layer' => 'integer',
        'max_layer' => 'integer',
    ];
}
