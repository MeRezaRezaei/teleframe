<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

final class TfWallpaper extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_wallpapers';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'access_hash' => 'integer',
        'creator' => 'boolean',
        'default' => 'boolean',
        'pattern' => 'boolean',
        'dark' => 'boolean',
    ];
}
