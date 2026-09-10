<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type ConnectedBot (spec §4.1). */
final class TlConnectedBot extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_connected_bot_connected_bot';

    protected $guarded = [];
}
