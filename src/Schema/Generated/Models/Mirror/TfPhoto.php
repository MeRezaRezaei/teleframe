<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

final class TfPhoto extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_photos';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'access_hash' => 'integer',
        'date' => 'integer',
        'dc_id' => 'integer',
        'has_stickers' => 'boolean',
    ];
}
