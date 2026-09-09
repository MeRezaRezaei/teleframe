<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param other_updates (table tl_updates_difference_difference__other_updates). */
final class TlUpdatesDifferenceDifferenceOther_updates extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_updates_difference_difference__other_updates';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
