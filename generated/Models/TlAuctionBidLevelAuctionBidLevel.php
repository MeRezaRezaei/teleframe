<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for auctionBidLevel of AuctionBidLevel (crc32 310240cc). */
final class TlAuctionBidLevelAuctionBidLevel extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_auction_bid_level_auction_bid_level';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'pos' => 'int',
        'amount' => 'int',
        'date' => 'int',
    ];
}
