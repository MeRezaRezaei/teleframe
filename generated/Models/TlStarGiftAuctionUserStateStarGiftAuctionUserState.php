<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for starGiftAuctionUserState of StarGiftAuctionUserState (crc32 2eeed1c4). */
final class TlStarGiftAuctionUserStateStarGiftAuctionUserState extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_star_gift_auction_user_state_star_gift_auc_62491a9be5e4';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'returned' => 'bool',
        'bid_amount' => 'int',
        'bid_date' => 'int',
        'min_bid_amount' => 'int',
        'bid_peer' => 'string',
        'acquired_count' => 'int',
    ];
}
