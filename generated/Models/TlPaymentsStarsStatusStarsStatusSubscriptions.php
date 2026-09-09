<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param subscriptions (table tl_payments_stars_status_stars_status__subscriptions). */
final class TlPaymentsStarsStatusStarsStatusSubscriptions extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_payments_stars_status_stars_status__subscriptions';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
