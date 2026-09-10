<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftAuctionStateStarGiftAuctionStateBid_levels;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftAuctionStateStarGiftAuctionStateRounds;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftAuctionStateStarGiftAuctionStateTop_bidders;

/** Constructor model for starGiftAuctionState of StarGiftAuctionState (crc32 771a4e66). */
final class TlStarGiftAuctionStateStarGiftAuctionState extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_star_gift_auction_state_star_gift_auction_state';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'version' => 'int',
        'start_date' => 'int',
        'end_date' => 'int',
        'min_bid_amount' => 'int',
        'next_round_at' => 'int',
        'last_gift_num' => 'int',
        'gifts_left' => 'int',
        'current_round' => 'int',
        'total_rounds' => 'int',
    ];

    public function bidLevels(): HasMany
    {
        return $this->tlChild(TlStarGiftAuctionStateStarGiftAuctionStateBid_levels::class);
    }
    public function topBidders(): HasMany
    {
        return $this->tlChild(TlStarGiftAuctionStateStarGiftAuctionStateTop_bidders::class);
    }
    public function rounds(): HasMany
    {
        return $this->tlChild(TlStarGiftAuctionStateStarGiftAuctionStateRounds::class);
    }
}
