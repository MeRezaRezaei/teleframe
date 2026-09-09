<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param users (table tl_payments_saved_star_gifts_saved_star_gifts__users). */
final class TlPaymentsSavedStarGiftsSavedStarGiftsUsers extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_payments_saved_star_gifts_saved_star_gifts__users';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
