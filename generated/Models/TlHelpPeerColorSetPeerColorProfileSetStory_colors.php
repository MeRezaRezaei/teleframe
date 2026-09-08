<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param story_colors (table tl_help_peer_color_set_peer_color_profile_set_d09cf1f8f0b4). */
final class TlHelpPeerColorSetPeerColorProfileSetStory_colors extends TlAnchorModel
{
    protected $table = 'tl_help_peer_color_set_peer_color_profile_set_d09cf1f8f0b4';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'value' => 'int',
    ];
}
