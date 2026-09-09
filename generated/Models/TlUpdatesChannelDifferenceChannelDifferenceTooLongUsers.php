<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param users (table tl_updates_channel_difference_channel_differe_0962ef95307b). */
final class TlUpdatesChannelDifferenceChannelDifferenceTooLongUsers extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_updates_channel_difference_channel_differe_0962ef95307b';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
