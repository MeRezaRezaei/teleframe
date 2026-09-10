<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsStarGiftAuctionStateStarGiftAuctionState;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftActiveAuctionStateStarGiftActiveAuctionState;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateStarGiftAuctionState;

/** Anchor model for TL type StarGiftAuctionState (spec §4.1). */
final class TlStarGiftAuctionState extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_star_gift_auction_state_star_gift_auction_state';

    protected $guarded = [];

    public function state(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateStarGiftAuctionState::class, 'state');
    }
    public function statePaymentsStarGiftAuctionState(): HasMany
    {
        return $this->hasMany(TlPaymentsStarGiftAuctionStateStarGiftAuctionState::class, 'state');
    }
    public function stateStarGiftActiveAuctionState(): HasMany
    {
        return $this->hasMany(TlStarGiftActiveAuctionStateStarGiftActiveAuctionState::class, 'state');
    }
}
