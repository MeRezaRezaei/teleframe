<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsStarGiftAuctionAcquiredGifts3ef8dccf2514Chats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsStarGiftAuctionAcquiredGifts3ef8dccf2514Gifts;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsStarGiftAuctionAcquiredGifts3ef8dccf2514Users;

/** Constructor model for payments.starGiftAuctionAcquiredGifts of payments.StarGiftAuctionAcquiredGifts (crc32 7d5bd1f0). */
final class TlPaymentsStarGiftAuctionAcquiredGiftsStarGiftAuctionAcquiredGifts extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_payments_star_gift_auction_acquired_gifts__3ef8dccf2514';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function gifts(): HasMany
    {
        return $this->tlChild(TlPaymentsStarGiftAuctionAcquiredGifts3ef8dccf2514Gifts::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlPaymentsStarGiftAuctionAcquiredGifts3ef8dccf2514Users::class);
    }
    public function chats(): HasMany
    {
        return $this->tlChild(TlPaymentsStarGiftAuctionAcquiredGifts3ef8dccf2514Chats::class);
    }
}
