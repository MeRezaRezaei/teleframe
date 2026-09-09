<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatThemeChatThemeUniqueGift;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionStarGift;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionStarGiftPurchaseOffer;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionStarGiftPurchaseOfferDeclined;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionStarGiftUnique;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsStarGiftAuctionStateStarGiftAuctionState;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsUniqueStarGiftUniqueStarGift;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSavedStarGiftSavedStarGift;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftActiveAuctionStateStarGiftActiveAuctionState;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarsTransactionStarsTransaction;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWebPageAttributeWebPageAttributeStarGiftAuction;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWebPageAttributeWebPageAttributeUniqueStarGift;

/** Anchor model for TL type StarGift (spec §4.1). */
final class TlStarGift extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_star_gift';

    protected $guarded = [];

    public function gift(): HasMany
    {
        return $this->hasMany(TlMessageActionMessageActionStarGift::class, 'gift');
    }
    public function giftChatThemeUniqueGift(): HasMany
    {
        return $this->hasMany(TlChatThemeChatThemeUniqueGift::class, 'gift');
    }
    public function giftMessageActionStarGiftPurchaseOffer(): HasMany
    {
        return $this->hasMany(TlMessageActionMessageActionStarGiftPurchaseOffer::class, 'gift');
    }
    public function giftMessageActionStarGiftPurchaseOfferDeclined(): HasMany
    {
        return $this->hasMany(TlMessageActionMessageActionStarGiftPurchaseOfferDeclined::class, 'gift');
    }
    public function giftMessageActionStarGiftUnique(): HasMany
    {
        return $this->hasMany(TlMessageActionMessageActionStarGiftUnique::class, 'gift');
    }
    public function giftPaymentsStarGiftAuctionState(): HasMany
    {
        return $this->hasMany(TlPaymentsStarGiftAuctionStateStarGiftAuctionState::class, 'gift');
    }
    public function giftPaymentsUniqueStarGift(): HasMany
    {
        return $this->hasMany(TlPaymentsUniqueStarGiftUniqueStarGift::class, 'gift');
    }
    public function giftSavedStarGift(): HasMany
    {
        return $this->hasMany(TlSavedStarGiftSavedStarGift::class, 'gift');
    }
    public function giftStarGiftActiveAuctionState(): HasMany
    {
        return $this->hasMany(TlStarGiftActiveAuctionStateStarGiftActiveAuctionState::class, 'gift');
    }
    public function giftWebPageAttributeStarGiftAuction(): HasMany
    {
        return $this->hasMany(TlWebPageAttributeWebPageAttributeStarGiftAuction::class, 'gift');
    }
    public function giftWebPageAttributeUniqueStarGift(): HasMany
    {
        return $this->hasMany(TlWebPageAttributeWebPageAttributeUniqueStarGift::class, 'gift');
    }
    public function stargift(): HasMany
    {
        return $this->hasMany(TlStarsTransactionStarsTransaction::class, 'stargift');
    }
}
