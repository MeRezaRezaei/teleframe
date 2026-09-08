<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param dark_colors (table tl_peer_color_peer_color_collectible__dark_colors). */
final class TlPeerColorPeerColorCollectibleDark_colors extends TlAnchorModel
{
    protected $table = 'tl_peer_color_peer_color_collectible__dark_colors';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'value' => 'int',
    ];
}
