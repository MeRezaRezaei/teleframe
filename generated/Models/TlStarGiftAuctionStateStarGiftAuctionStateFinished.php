<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for starGiftAuctionStateFinished of StarGiftAuctionState (crc32 972dabbf). */
final class TlStarGiftAuctionStateStarGiftAuctionStateFinished extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_star_gift_auction_state_star_gift_auction__3ffddf14cd70';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'start_date' => 'int',
        'end_date' => 'int',
        'average_price' => 'int',
        'listed_count' => 'int',
        'fragment_listed_count' => 'int',
        'fragment_listed_url' => 'string',
    ];
}
