<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for starGiftAuctionAcquiredGift of StarGiftAuctionAcquiredGift (crc32 42b00348). */
final class TlStarGiftAuctionAcquiredGiftStarGiftAuctionAcquiredGift extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_star_gift_auction_acquired_gift_star_gift__f6508cc9bcc2';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'name_hidden' => 'bool',
        'peer' => 'string',
        'date' => 'int',
        'bid_amount' => 'int',
        'round' => 'int',
        'pos' => 'int',
        'message' => 'string',
        'gift_num' => 'int',
    ];
}
