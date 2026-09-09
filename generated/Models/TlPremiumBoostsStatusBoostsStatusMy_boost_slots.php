<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param my_boost_slots (table tl_premium_boosts_status_boosts_status__my_boost_slots). */
final class TlPremiumBoostsStatusBoostsStatusMy_boost_slots extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_premium_boosts_status_boosts_status__my_boost_slots';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'value' => 'int',
    ];
}
