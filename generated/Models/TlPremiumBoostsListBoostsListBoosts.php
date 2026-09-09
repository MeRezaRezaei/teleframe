<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param boosts (table tl_premium_boosts_list_boosts_list__boosts). */
final class TlPremiumBoostsListBoostsListBoosts extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_premium_boosts_list_boosts_list__boosts';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
