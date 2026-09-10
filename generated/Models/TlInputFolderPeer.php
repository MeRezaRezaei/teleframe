<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type InputFolderPeer (spec §4.1). */
final class TlInputFolderPeer extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_input_folder_peer_input_folder_peer';

    protected $guarded = [];
}
