<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param colors (table tl_help_peer_colors_peer_colors__colors). */
final class TlHelpPeerColorsPeerColorsColors extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_help_peer_colors_peer_colors__colors';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
