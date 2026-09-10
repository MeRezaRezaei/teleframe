<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionStarGiftPurchaseOffer;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionStarGiftPurchaseOfferDeclined;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionStarGiftUnique;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionSuggestedPostApproval;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionSuggestedPostSuccess;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsStarsStatusStarsStatus;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarRefProgramStarRefProgram;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarsRevenueStatusStarsRevenueStatus;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarsTransactionStarsTransaction;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSuggestedPostSuggestedPost;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateStarsBalance;

/** Anchor model for TL type StarsAmount (spec §4.1). */
final class TlStarsAmount extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_stars_amount_stars_amount';

    protected $guarded = [];

    public function amount(): HasMany
    {
        return $this->hasMany(TlStarsTransactionStarsTransaction::class, 'amount');
    }
    public function availableBalance(): HasMany
    {
        return $this->hasMany(TlStarsRevenueStatusStarsRevenueStatus::class, 'available_balance');
    }
    public function balance(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateStarsBalance::class, 'balance');
    }
    public function balancePaymentsStarsStatus(): HasMany
    {
        return $this->hasMany(TlPaymentsStarsStatusStarsStatus::class, 'balance');
    }
    public function currentBalance(): HasMany
    {
        return $this->hasMany(TlStarsRevenueStatusStarsRevenueStatus::class, 'current_balance');
    }
    public function dailyRevenuePerUser(): HasMany
    {
        return $this->hasMany(TlStarRefProgramStarRefProgram::class, 'daily_revenue_per_user');
    }
    public function overallRevenue(): HasMany
    {
        return $this->hasMany(TlStarsRevenueStatusStarsRevenueStatus::class, 'overall_revenue');
    }
    public function price(): HasMany
    {
        return $this->hasMany(TlMessageActionMessageActionSuggestedPostApproval::class, 'price');
    }
    public function priceMessageActionStarGiftPurchaseOffer(): HasMany
    {
        return $this->hasMany(TlMessageActionMessageActionStarGiftPurchaseOffer::class, 'price');
    }
    public function priceMessageActionStarGiftPurchaseOfferDeclined(): HasMany
    {
        return $this->hasMany(TlMessageActionMessageActionStarGiftPurchaseOfferDeclined::class, 'price');
    }
    public function priceMessageActionSuggestedPostSuccess(): HasMany
    {
        return $this->hasMany(TlMessageActionMessageActionSuggestedPostSuccess::class, 'price');
    }
    public function priceSuggestedPost(): HasMany
    {
        return $this->hasMany(TlSuggestedPostSuggestedPost::class, 'price');
    }
    public function resaleAmount(): HasMany
    {
        return $this->hasMany(TlMessageActionMessageActionStarGiftUnique::class, 'resale_amount');
    }
    public function starrefAmount(): HasMany
    {
        return $this->hasMany(TlStarsTransactionStarsTransaction::class, 'starref_amount');
    }
}
