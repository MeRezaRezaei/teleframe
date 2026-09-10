<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type payments.StarGiftAuctionAcquiredGifts (spec §4.1). */
final class TlPaymentsStarGiftAuctionAcquiredGifts extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_payments_star_gift_auction_acquired_gifts__3ef8dccf2514';

    protected $guarded = [];
}
