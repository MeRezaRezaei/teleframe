<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Domain model for wallpapers (TL types: WallPaper). */
final class TlWallpaper extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tf_wallpapers';

    protected $guarded = [];
}
