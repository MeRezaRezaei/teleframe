<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param exclude_peers (table tl_dialog_filter_dialog_filter__exclude_peers). */
final class TlDialogFilterDialogFilterExclude_peers extends TlAnchorModel
{
    protected $table = 'tl_dialog_filter_dialog_filter__exclude_peers';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
