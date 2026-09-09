<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param rounds (table tl_star_gift_auction_state_star_gift_auction_state__rounds). */
final class TlStarGiftAuctionStateStarGiftAuctionStateRounds extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_star_gift_auction_state_star_gift_auction_state__rounds';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
