<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGift;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftAuctionState;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftAuctionUserState;

/** Constructor model for starGiftActiveAuctionState of StarGiftActiveAuctionState (crc32 d31bc45d). */
final class TlStarGiftActiveAuctionStateStarGiftActiveAuctionState extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_star_gift_active_auction_state_star_gift_a_0f6a2e549dbc';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function gift(): BelongsTo
    {
        return $this->belongsTo(TlStarGift::class, 'gift');
    }
    public function state(): BelongsTo
    {
        return $this->belongsTo(TlStarGiftAuctionState::class, 'state');
    }
    public function userState(): BelongsTo
    {
        return $this->belongsTo(TlStarGiftAuctionUserState::class, 'user_state');
    }
}
