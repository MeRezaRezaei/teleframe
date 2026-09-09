<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param chats (table tl_payments_star_gift_auction_acquired_gifts__cd07c7958d0f). */
final class TlPaymentsStarGiftAuctionAcquiredGifts3ef8dccf2514Chats extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_payments_star_gift_auction_acquired_gifts__cd07c7958d0f';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
