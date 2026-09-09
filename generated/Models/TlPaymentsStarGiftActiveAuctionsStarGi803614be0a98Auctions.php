<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param auctions (table tl_payments_star_gift_active_auctions_star_gi_ccb0d8ae92aa). */
final class TlPaymentsStarGiftActiveAuctionsStarGi803614be0a98Auctions extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_payments_star_gift_active_auctions_star_gi_ccb0d8ae92aa';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
