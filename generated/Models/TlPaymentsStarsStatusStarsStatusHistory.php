<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param history (table tl_payments_stars_status_stars_status__history). */
final class TlPaymentsStarsStatusStarsStatusHistory extends TlAnchorModel
{
    protected $table = 'tl_payments_stars_status_stars_status__history';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
