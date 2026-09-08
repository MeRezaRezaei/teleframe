<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param winners (table tl_stars_giveaway_option_stars_giveaway_option__winners). */
final class TlStarsGiveawayOptionStarsGiveawayOptionWinners extends TlAnchorModel
{
    protected $table = 'tl_stars_giveaway_option_stars_giveaway_option__winners';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
