<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfWallpapersSetting extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_wallpapers_settings';
    protected $primaryKey = 'parent_id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'blur' => 'boolean',
        'motion' => 'boolean',
        'background_color' => 'integer',
        'second_background_color' => 'integer',
        'third_background_color' => 'integer',
        'fourth_background_color' => 'integer',
        'intensity' => 'integer',
        'rotation' => 'integer',
    ];
}
