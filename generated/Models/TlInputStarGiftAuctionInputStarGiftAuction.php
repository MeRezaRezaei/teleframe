<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for inputStarGiftAuction of InputStarGiftAuction (crc32 02e16c98). */
final class TlInputStarGiftAuctionInputStarGiftAuction extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_star_gift_auction_input_star_gift_auction';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'gift_id' => 'int',
    ];
}
