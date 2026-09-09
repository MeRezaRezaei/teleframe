<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param wallpapers (table tl_account_wall_papers_wall_papers__wallpapers). */
final class TlAccountWallPapersWallPapersWallpapers extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_account_wall_papers_wall_papers__wallpapers';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
