<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param media (table tl_bots_preview_info_preview_info__media). */
final class TlBotsPreviewInfoPreviewInfoMedia extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_bots_preview_info_preview_info__media';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
