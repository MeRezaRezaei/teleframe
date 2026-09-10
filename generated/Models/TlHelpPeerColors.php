<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type help.PeerColors (spec §4.1). */
final class TlHelpPeerColors extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_help_peer_colors_peer_colors';

    protected $guarded = [];
}
