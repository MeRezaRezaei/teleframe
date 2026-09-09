<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param colors (table tl_help_peer_color_set_peer_color_set__colors). */
final class TlHelpPeerColorSetPeerColorSetColors extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_help_peer_color_set_peer_color_set__colors';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'value' => 'int',
    ];
}
