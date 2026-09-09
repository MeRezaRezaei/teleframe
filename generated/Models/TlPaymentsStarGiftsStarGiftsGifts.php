<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param gifts (table tl_payments_star_gifts_star_gifts__gifts). */
final class TlPaymentsStarGiftsStarGiftsGifts extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_payments_star_gifts_star_gifts__gifts';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
