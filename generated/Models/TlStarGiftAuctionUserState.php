<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsStarGiftAuctionStateStarGiftAuctionState;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftActiveAuctionStateStarGiftActiveAuctionState;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateStarGiftAuctionUserState;

/** Anchor model for TL type StarGiftAuctionUserState (spec §4.1). */
final class TlStarGiftAuctionUserState extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_star_gift_auction_user_state';

    protected $guarded = [];

    public function userState(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateStarGiftAuctionUserState::class, 'user_state');
    }
    public function userStatePaymentsStarGiftAuctionState(): HasMany
    {
        return $this->hasMany(TlPaymentsStarGiftAuctionStateStarGiftAuctionState::class, 'user_state');
    }
    public function userStateStarGiftActiveAuctionState(): HasMany
    {
        return $this->hasMany(TlStarGiftActiveAuctionStateStarGiftActiveAuctionState::class, 'user_state');
    }
}
