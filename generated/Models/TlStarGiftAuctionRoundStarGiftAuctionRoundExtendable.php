<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for starGiftAuctionRoundExtendable of StarGiftAuctionRound (crc32 0aa021e5). */
final class TlStarGiftAuctionRoundStarGiftAuctionRoundExtendable extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_star_gift_auction_round_star_gift_auction__a0b7925a30f9';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'num' => 'int',
        'duration' => 'int',
        'extend_top' => 'int',
        'extend_window' => 'int',
    ];
}
