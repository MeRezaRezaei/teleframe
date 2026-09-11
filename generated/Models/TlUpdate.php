<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Domain model for updates (TL types: Update, updates.ChannelDifference, updates.Difference, updates.State). */
final class TlUpdate extends TlAnchorModel
{
    use AccountScoped, PeerResolution;

    protected $table = 'tf_updates';

    protected $guarded = [];
}
