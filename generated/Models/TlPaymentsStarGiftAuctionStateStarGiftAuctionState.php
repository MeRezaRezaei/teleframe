<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsStarGiftAuctionStateStarGiftBa2a6a814fffChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsStarGiftAuctionStateStarGiftBa2a6a814fffUsers;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGift;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftAuctionState;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftAuctionUserState;

/** Constructor model for payments.starGiftAuctionState of payments.StarGiftAuctionState (crc32 6b39f4ec). */
final class TlPaymentsStarGiftAuctionStateStarGiftAuctionState extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_payments_star_gift_auction_state_star_gift_ba2a6a814fff';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'timeout' => 'int',
    ];

    public function users(): HasMany
    {
        return $this->tlChild(TlPaymentsStarGiftAuctionStateStarGiftBa2a6a814fffUsers::class);
    }
    public function chats(): HasMany
    {
        return $this->tlChild(TlPaymentsStarGiftAuctionStateStarGiftBa2a6a814fffChats::class);
    }

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
