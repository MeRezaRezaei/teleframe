<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for starGiftAuctionStateNotModified of StarGiftAuctionState (crc32 fe333952). */
final class TlStarGiftAuctionStateStarGiftAuctionStateNotModified extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_star_gift_auction_state_star_gift_auction__ba2b64ef8cf9';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
